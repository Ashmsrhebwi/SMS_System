<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Validate credentials WITHOUT logging in (rate-limited)
        $user = $request->validateCredentials();

        // ── Step 2: OTP ───────────────────────────────────────────────────────

        // Rate-limit OTP send requests: 5 per hour per email address
        $otpRateLimiterKey = 'otp-request:' . strtolower($user->email);

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($otpRateLimiterKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($otpRateLimiterKey);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Too many verification code requests. Please wait ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        // Generate and send OTP
        [$sessionToken] = OtpVerificationController::generateAndSendOtp($user, $request);

        \Illuminate\Support\Facades\RateLimiter::hit($otpRateLimiterKey, 3600);

        // Store pending auth state in session (10-minute window for OTP page)
        $request->session()->put('auth_pending', [
            'user_id'       => $user->id,
            'session_token' => $sessionToken,
            'remember'      => $request->boolean('remember'),
            'expires_at'    => now()->addMinutes(10)->timestamp,
        ]);

        AuditLogger::logSecurity('otp_requested', $user->id);

        return redirect()->route('login.otp');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            AuditLogger::logAuth('logout', $user);
        }

        return redirect('/');
    }
}
