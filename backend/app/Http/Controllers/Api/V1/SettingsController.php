<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Enums\AuthEvent;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Requests\UpdateEmailRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Resources\UserResource;
use App\Services\AccountDeletionService;
use App\Services\AuthAuditService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthAuditService $audit) {}

    public function updateAccount(UpdateAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return $this->success([
            'user' => new UserResource($user->load('roles')),
        ], 'Account details updated.');
    }

    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'email' => $request->validated()['email'],
            // Changing the address invalidates the previous confirmation.
            'email_verified_at' => null,
            'status' => UserStatus::Pending,
        ])->save();

        // Re-run the verification flow against the new address.
        event(new Registered($user));
        $user->sendEmailVerificationNotification();

        $this->audit->log(AuthEvent::EmailChanged, $user, request: $request);

        return $this->success([
            'user' => new UserResource($user->load('roles')),
        ], 'Email updated. Check your inbox to confirm the new address.');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill(['password' => $request->validated()['password']])->save();

        // Every other session's token is now stale.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        $this->audit->log(AuthEvent::PasswordChanged, $user, request: $request);

        return $this->success(null, 'Password changed. Other sessions have been signed out.');
    }

    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return $this->success([
            'user' => new UserResource($user->load('roles')),
        ], 'Preferences saved.');
    }

    /**
     * Permanently delete the signed-in user's own account and everything tied to
     * it. Guarded by a typed confirmation phrase (validated in the form request)
     * and closed to admins, who must be removed through admin tooling.
     */
    public function destroyAccount(DeleteAccountRequest $request, AccountDeletionService $accounts): JsonResponse
    {
        $user = $request->user();

        if ($user->account_type === AccountType::Admin) {
            return $this->error('Administrator accounts cannot be deleted from settings.', null, 403);
        }

        $this->audit->log(AuthEvent::AccountDeleted, $user, request: $request);

        $accounts->delete($user);

        return $this->success(null, 'Your account has been permanently deleted.');
    }
}
