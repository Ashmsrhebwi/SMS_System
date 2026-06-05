<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    private const OTP_TTL_MINUTES   = 5;
    private const MAX_OTP_REQUESTS  = 5;   // per hour per user
    private const MAX_OTP_ATTEMPTS  = 3;   // per OTP record

    public function canRequest(User $user): bool
    {
        return !RateLimiter::tooManyAttempts($this->requestKey($user), self::MAX_OTP_REQUESTS);
    }

    public function remainingRequestSeconds(User $user): int
    {
        return RateLimiter::availableIn($this->requestKey($user));
    }

    /**
     * Generate, store (hashed), and email an OTP.
     * Returns false if rate-limited.
     */
    public function generate(User $user): bool
    {
        if (!$this->canRequest($user)) {
            return false;
        }

        // Invalidate any active OTPs
        LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $plainOtp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginOtp::create([
            'user_id'    => $user->id,
            'otp_hash'   => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        RateLimiter::hit($this->requestKey($user), 3600); // 1-hour window

        $user->notify(new LoginOtpNotification($plainOtp));

        return true;
    }

    /**
     * Verify a submitted OTP.
     * Returns 'valid' | 'invalid' | 'expired' | 'exhausted' | 'not_found'
     */
    public function verify(User $user, string $submittedOtp): string
    {
        $otp = LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp) {
            return 'not_found';
        }

        if ($otp->isExpired()) {
            return 'expired';
        }

        if ($otp->isExhausted()) {
            return 'exhausted';
        }

        if (!Hash::check($submittedOtp, $otp->otp_hash)) {
            $otp->increment('attempts');
            return $otp->attempts >= self::MAX_OTP_ATTEMPTS ? 'exhausted' : 'invalid';
        }

        $otp->update(['used_at' => now()]);
        RateLimiter::clear($this->requestKey($user));

        return 'valid';
    }

    private function requestKey(User $user): string
    {
        return 'otp_request|' . $user->id;
    }
}
