<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Force HTTPS when APP_URL is https — fixes route() generating http:// behind shared-hosting proxies
        if (str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        // Enterprise password policy: 12+ chars, mixed case, number, symbol
        Password::defaults(function () {
            return Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Login rate limiter — 5 attempts per minute per email+IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->input('email') . '|' . $request->ip());
        });
    }
}
