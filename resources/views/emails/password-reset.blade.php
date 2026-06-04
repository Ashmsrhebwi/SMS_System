@extends('emails.layout')

@section('title', 'Reset Your Password – FeRa Clinic')

@section('content')
<p class="greeting">Hello, {{ $user->name }}</p>
<p class="text">
  We received a request to reset the password for your FeRa Clinic SMS Platform account. Click the button below to set a new password.
</p>

<!-- Reset Button -->
<div style="text-align:center;margin:32px 0;">
  <a href="{{ $resetUrl }}"
     style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:12px;letter-spacing:0.3px;">
    Reset My Password
  </a>
</div>

<p class="text" style="text-align:center;font-size:13px;color:#64748b;">
  Or copy and paste this link into your browser:
</p>
<p style="word-break:break-all;font-size:12px;color:#6366f1;text-align:center;margin-bottom:20px;">
  {{ $resetUrl }}
</p>

<div class="divider"></div>

<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;margin:20px 0;">
  <p style="font-size:13px;color:#92400e;font-weight:600;margin:0 0 4px;">
    ⏱ Link Expiry
  </p>
  <p style="font-size:13px;color:#78350f;margin:0;line-height:1.5;">
    This reset link expires in <strong>30 minutes</strong> and can only be used once. If you did not request a password reset, no action is needed — your password remains unchanged.
  </p>
</div>

<p class="text">
  For security, this link is single-use. Once you reset your password, the link will no longer be valid.
</p>

<div class="divider"></div>

<p style="font-size:12px;color:#94a3b8;">
  Request details:<br>
  IP Address: <strong>{{ request()->ip() }}</strong><br>
  Time: <strong>{{ now()->format('d M Y H:i:s') }} UTC</strong>
</p>
@endsection
