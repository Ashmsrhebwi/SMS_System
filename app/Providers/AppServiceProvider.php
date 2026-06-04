<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Helpers/helpers.php');
    }

    public function boot(): void
    {
        // ── Password Policy ────────────────────────────────────────────────────
        // Applied everywhere Rules\Password::defaults() is used
        Password::defaults(function () {
            return Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // ── Default mail from address ──────────────────────────────────────────
        Mail::alwaysFrom('info@feraclinic.com', 'FeRa Clinic');

        // ── Auth event listeners (login/logout already handled by OTP flow) ────
        // These fire on the standard Auth facade events (API / direct login paths)
        Event::listen(Login::class, function (Login $event) {
            // OTP flow logs manually; skip double-logging for web guard
            if ($event->guard !== 'web') {
                AuditLogger::logAuth('login', $event->user);
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            // Logout is logged in AuthenticatedSessionController for web guard
            if ($event->guard !== 'web') {
                AuditLogger::logAuth('logout', $event->user);
            }
        });
    }
}
