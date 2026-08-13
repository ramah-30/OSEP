<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuthEvent;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Requests\UpdateEmailRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Resources\UserResource;
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
}
