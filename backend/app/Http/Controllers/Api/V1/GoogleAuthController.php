<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    /**
     * Entered by a full page navigation from the SPA, so an unconfigured server
     * must bounce back to the frontend's callback screen — dumping a JSON error
     * into the address bar is not an answer a user can act on.
     */
    public function redirect(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->isConfigured()) {
            return $request->expectsJson() && ! $request->acceptsHtml()
                ? $this->error('Google sign-in is not configured on this server yet.', null, 503)
                : $this->redirectToFrontend(['error' => 'not_configured']);
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Google sends the browser here. We cannot put a Sanctum token in the URL
     * and leave it in history, so we stash it behind a short-lived one-time
     * code the SPA immediately exchanges.
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return $this->redirectToFrontend(['error' => 'not_configured']);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable) {
            return $this->redirectToFrontend(['error' => 'google_failed']);
        }

        if (! $googleUser->getEmail()) {
            return $this->redirectToFrontend(['error' => 'no_email']);
        }

        try {
            $result = $this->auth->loginWithGoogle($googleUser, $request);
        } catch (Throwable) {
            return $this->redirectToFrontend(['error' => 'account_unavailable']);
        }

        $code = Str::random(48);

        Cache::put("google-auth:{$code}", [
            'token' => $result['token'],
            'user_id' => $result['user']->id,
        ], now()->addMinutes(2));

        return $this->redirectToFrontend(['code' => $code]);
    }

    /**
     * Exchange the one-time code for the bearer token.
     */
    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:48'],
        ]);

        $key = "google-auth:{$validated['code']}";
        $payload = Cache::pull($key);

        if (! $payload) {
            return $this->error('This sign-in link has expired. Please try again.', null, 422);
        }

        $user = User::with('roles')->find($payload['user_id']);

        if (! $user) {
            return $this->error('This sign-in link is no longer valid.', null, 422);
        }

        return $this->success([
            'user' => new UserResource($user),
            'token' => $payload['token'],
            'token_type' => 'Bearer',
        ], 'Signed in with Google.');
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /**
     * @param  array<string, string>  $query
     */
    private function redirectToFrontend(array $query): RedirectResponse
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/auth/google/callback?'.http_build_query($query);

        return redirect()->away($url);
    }
}
