<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\AuthEvent;
use App\Enums\UserStatus;
use App\Exceptions\AuthFailedException;
use App\Models\ClientProfile;
use App\Models\PlannerProfile;
use App\Models\User;
use App\Models\VendorCategory;
use App\Models\VendorProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /** Token lifetime when "remember me" is ticked. */
    private const REMEMBER_HOURS = 720; // 30 days

    /** Token lifetime for a normal session. */
    private const DEFAULT_HOURS = 12;

    public function __construct(private readonly AuthAuditService $audit) {}

    /**
     * Create an account, attach the role matching the chosen account type and
     * send the verification email.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data, Request $request): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'account_type' => $data['account_type'],
                'country' => $data['country'],
                'status' => UserStatus::Pending,
            ]);

            // account_type and role names are intentionally identical, which is
            // what keeps new account types a seed-only change.
            $user->assignRole($data['account_type']);

            $this->ensureProfile($user);

            if ($user->account_type === AccountType::Vendor) {
                $categoryId = $data['category_id']
                    ?? VendorCategory::firstOrCreate(
                        ['name' => $data['category_name']],
                        ['is_custom' => true, 'is_active' => true, 'created_by' => $user->id],
                    )->id;

                $user->vendorProfile->forceFill(['category_id' => $categoryId])->save();
            }

            return $user;
        });

        event(new Registered($user));
        $user->sendEmailVerificationNotification();

        $this->audit->log(AuthEvent::Registered, $user, request: $request, metadata: [
            'account_type' => $user->account_type->value,
        ]);

        return [
            'user' => $user->load('roles'),
            'token' => $this->issueToken($user, remember: false),
        ];
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws AuthFailedException
     */
    public function login(string $email, string $password, bool $remember, Request $request): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            // The reason is recorded for support; the caller is told nothing
            // that would let them work out whether the address is registered.
            $this->audit->log(AuthEvent::LoginFailed, $user, $email, $request, [
                'reason' => match (true) {
                    ! $user => 'unknown_email',
                    ! $user->password => 'password_not_set',
                    default => 'wrong_password',
                },
            ]);

            throw new AuthFailedException;
        }

        // Kept distinct from the credential message: retrying cannot fix this,
        // so the person needs to know to contact support rather than guess.
        if ($user->isSuspended()) {
            $this->audit->log(AuthEvent::LoginBlocked, $user, $email, $request, ['reason' => 'suspended']);

            throw new AuthFailedException('This account has been suspended. Please contact support.');
        }

        $this->audit->log(AuthEvent::LoginSuccess, $user, $email, $request, ['remember' => $remember]);

        return [
            'user' => $user->load('roles'),
            'token' => $this->issueToken($user, $remember),
        ];
    }

    public function logout(User $user, Request $request): void
    {
        $user->currentAccessToken()?->delete();

        $this->audit->log(AuthEvent::Logout, $user, request: $request);
    }

    /**
     * Sign in — or transparently create — a user from a Google profile.
     *
     * @return array{user: User, token: string}
     */
    public function loginWithGoogle(\Laravel\Socialite\Contracts\User $googleUser, Request $request): array
    {
        $email = strtolower((string) $googleUser->getEmail());

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $user->avatar_url ?: $googleUser->getAvatar(),
                // Google has already proven ownership of the address.
                'email_verified_at' => $user->email_verified_at ?? now(),
                'status' => $user->isSuspended() ? UserStatus::Suspended : UserStatus::Active,
            ])->save();
        } else {
            [$first, $last] = $this->splitName($googleUser->getName() ?: $email);

            $user = User::create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                // Defaults to Client; the user can be upgraded later without
                // blocking the OAuth hand-off on a profession question.
                'account_type' => AccountType::Client,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(AccountType::Client->value);
            $this->ensureProfile($user);
        }

        if ($user->isSuspended()) {
            $this->audit->log(AuthEvent::LoginBlocked, $user, $email, $request, ['reason' => 'suspended']);

            throw new AuthFailedException('This account has been suspended. Please contact support.');
        }

        $this->audit->log(AuthEvent::GoogleLogin, $user, $email, $request);

        return [
            'user' => $user->load('roles'),
            'token' => $this->issueToken($user, remember: true),
        ];
    }

    /**
     * Create the empty profile row that matches the user's account type, so the
     * profile endpoints and dashboards always have a record to read and update.
     */
    public function ensureProfile(User $user): void
    {
        match ($user->account_type) {
            AccountType::EventPlanner => $this->ensurePlannerProfile($user),
            AccountType::Client => ClientProfile::firstOrCreate(['user_id' => $user->id]),
            AccountType::Vendor => VendorProfile::firstOrCreate(['user_id' => $user->id]),
        };
    }

    private function ensurePlannerProfile(User $user): void
    {
        $profile = PlannerProfile::firstOrCreate(['user_id' => $user->id]);

        if (! $profile->booking_slug) {
            $profile->update(['booking_slug' => $this->generateBookingSlug($user)]);
        }
    }

    private function generateBookingSlug(User $user): string
    {
        $base = Str::slug("{$user->first_name} {$user->last_name}");
        $slug = $base;
        $i = 2;

        while (PlannerProfile::where('booking_slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function issueToken(User $user, bool $remember): string
    {
        $hours = $remember ? self::REMEMBER_HOURS : self::DEFAULT_HOURS;

        return $user->createToken(
            'osep-spa',
            ['*'],
            now()->addHours($hours)
        )->plainTextToken;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        $first = array_shift($parts) ?: 'OSEP';
        $last = $parts ? implode(' ', $parts) : 'Member';

        return [Str::limit($first, 50, ''), Str::limit($last, 50, '')];
    }
}
