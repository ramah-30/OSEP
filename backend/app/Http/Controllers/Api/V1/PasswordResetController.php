<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuthEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuthAuditService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthAuditService $audit) {}

    /**
     * Confirms the address is registered before sending anything, so someone
     * who mistypes their email is told immediately rather than waiting for a
     * message that will never arrive.
     *
     * Note this is by explicit product choice: it does let a caller learn
     * whether an address has an account. The throttle (3/hour per address,
     * 10/hour per IP) is what keeps that from being enumerable at scale.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->string('email')->toString();
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->audit->log(
                AuthEvent::PasswordResetRequested,
                null,
                $email,
                $request,
                ['status' => 'unknown_email'],
            );

            return $this->error(
                'We could not find an account with that email address.',
                ['email' => ['We could not find an account with that email address.']],
                422,
            );
        }

        $status = Password::sendResetLink(['email' => $email]);

        $this->audit->log(
            AuthEvent::PasswordResetRequested,
            $user,
            $email,
            $request,
            ['status' => $status],
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->error(
                'A reset link was sent recently. Please check your inbox, or try again in a few minutes.',
                ['email' => [__($status)]],
                422,
            );
        }

        return $this->success(null, 'A reset link is on its way to your inbox.');
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                $user->forceFill(['password' => $password])->save();

                // Anything already signed in with the old password is cut off.
                $user->tokens()->delete();

                event(new PasswordReset($user));

                $this->audit->log(AuthEvent::PasswordReset, $user, $user->email, $request);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(
                'This reset link is invalid or has expired. Request a new one.',
                ['token' => [__($status)]],
                422,
            );
        }

        return $this->success(null, 'Password updated. You can now sign in.');
    }
}
