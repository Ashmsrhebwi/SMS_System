<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginOtp extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'otp_hash', 'session_token',
        'expires_at', 'attempts', 'used_at',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= 3;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed() && !$this->isExhausted();
    }

    // Generate a 6-digit OTP
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    // HMAC-SHA256 using the user's password hash as key — safe even if table is breached
    public static function hashCode(string $code, string $userPasswordHash): string
    {
        return hash_hmac('sha256', $code, $userPasswordHash);
    }

    public function verify(string $submittedCode, string $userPasswordHash): bool
    {
        $expected = self::hashCode($submittedCode, $userPasswordHash);
        return hash_equals($this->otp_hash, $expected);
    }
}
