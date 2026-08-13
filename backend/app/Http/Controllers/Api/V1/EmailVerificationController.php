<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuthEvent;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthAuditService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthAuditService $audit) {}

    /**
     * Landed on from the emailed signed link. Verifies, activates, then hands
     * the browser back to the SPA with a status flag it can render.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return $this->redirectToFrontend('invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectToFrontend('already-verified');
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'status' => $user->isSuspended() ? UserStatus::Suspended : UserStatus::Active,
        ])->save();

        event(new Verified($user));

        $this->audit->log(AuthEvent::EmailVerified, $user, $user->email, $request);

        return $this->redirectToFrontend('verified');
    }

    /**
     * Re-send the link. Answers identically for unknown addresses so it cannot
     * be used to probe which emails are registered.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        $user = User::where('email', strtolower($validated['email']))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            $this->audit->log(AuthEvent::VerificationResent, $user, $user->email, $request);
        }

        return $this->success(null, 'If that address needs confirming, a new link is on its way.');
    }

    private function redirectToFrontend(string $status): RedirectResponse
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/verify-email/callback?status='.$status;

        return redirect()->away($url);
    }
}
