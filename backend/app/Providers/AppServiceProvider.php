<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Credential-facing endpoints are throttled per identity *and* per IP, so
     * one attacker cannot lock every account out by hammering a single address.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perHour(3)->by(strtolower((string) $request->input('email'))),
            Limit::perHour(10)->by($request->ip()),
        ]);

        RateLimiter::for('verification-resend', fn (Request $request) => [
            Limit::perHour(3)->by(strtolower((string) $request->input('email'))),
            Limit::perHour(10)->by($request->ip()),
        ]);

        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by($request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
