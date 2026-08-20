<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Http\Controllers\Api\V1\Marketplace\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin moderation of client accounts: list, verify (admin-approved) and
 * suspend, mirroring how vendors are moderated in the marketplace.
 */
class ClientController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('users.*')
            ->leftJoin('client_profiles as cp', 'cp.user_id', '=', 'users.id')
            ->where('users.account_type', AccountType::Client->value)
            ->with('clientProfile')
            ->withCount('clientEvents as events_count');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        if ($request->query('verification') === 'verified') {
            $query->whereNotNull('cp.verified_at');
        } elseif ($request->query('verification') === 'pending') {
            $query->whereNull('cp.verified_at');
        }

        if ($request->filled('suspended')) {
            $request->boolean('suspended')
                ? $query->where('users.status', UserStatus::Suspended->value)
                : $query->where('users.status', '!=', UserStatus::Suspended->value);
        }

        $paginator = $query->latest('users.created_at')
            ->paginate((int) $request->integer('per_page', 20))->withQueryString();

        return $this->success([
            'clients' => $paginator->getCollection()->map(fn (User $u) => $this->present($u)),
            'meta' => $this->pageMeta($paginator),
        ]);
    }

    public function verify(Request $request, User $client): JsonResponse
    {
        $profile = $this->profileFor($client);
        $data = $request->validate(['verified' => ['required', 'boolean']]);

        $profile->update(['verified_at' => $data['verified'] ? now() : null]);

        Notification::create([
            'user_id' => $client->id,
            'type' => 'verification_updated',
            'title' => $data['verified'] ? 'Account verified' : 'Verification removed',
            'message' => $data['verified']
                ? 'Your account has been verified by an administrator.'
                : 'Your account verification has been removed.',
            'data' => [],
        ]);

        return $this->success(['client' => $this->present($client->fresh(['clientProfile']))],
            $data['verified'] ? 'Client verified.' : 'Verification removed.');
    }

    public function suspend(Request $request, User $client): JsonResponse
    {
        $this->profileFor($client);
        $data = $request->validate(['suspended' => ['required', 'boolean']]);

        $client->update(['status' => $data['suspended'] ? UserStatus::Suspended : UserStatus::Active]);

        if ($data['suspended']) {
            $client->tokens()->delete();
        }

        return $this->success(['client' => $this->present($client->fresh(['clientProfile']))],
            $data['suspended'] ? 'Client suspended.' : 'Client reinstated.');
    }

    private function profileFor(User $client): \App\Models\ClientProfile
    {
        abort_unless($client->account_type === AccountType::Client, 404);

        return $client->clientProfile()->firstOrCreate([]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $u): array
    {
        return [
            'id' => $u->id,
            'full_name' => $u->full_name,
            'email' => $u->email,
            'phone' => $u->phone,
            'avatar_url' => $u->avatar_url,
            'location' => $u->clientProfile?->location,
            'events_count' => (int) ($u->events_count ?? 0),
            'status' => $u->status->value,
            'is_suspended' => $u->status === UserStatus::Suspended,
            'is_verified' => $u->clientProfile?->verified_at !== null,
            'verified_at' => $u->clientProfile?->verified_at?->toIso8601String(),
        ];
    }
}
