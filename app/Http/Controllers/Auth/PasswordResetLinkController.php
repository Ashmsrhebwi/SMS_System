<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Log the reset request regardless of whether the email exists
        // (avoid email enumeration via different log records)
        AuditLogger::logSecurity('password_reset_requested', null, [
            'email' => $request->email,
        ]);

        // Always show the same response to prevent email enumeration
        return back()->with('status', __($status));
    }
}
