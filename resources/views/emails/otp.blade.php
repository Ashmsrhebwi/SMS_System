@extends('emails.layout')

@section('title', 'Your Login Code – FeRa Clinic')

@section('content')
<p class="greeting">Hello, {{ $user->name }}</p>
<p class="text">
  You are signing in to <strong>FeRa Clinic SMS Platform</strong>. Use the verification code below to complete your login.
</p>

<!-- OTP Code Box -->
<div style="text-align:center;margin:32px 0;">
  <div style="display:inline-block;background:#f0f4ff;border:2px solid #c7d2fe;border-radius:16px;padding:24px 48px;">
    <p style="font-size:12px;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">
      Verification Code
    </p>
    <p style="font-size:40px;font-weight:800;letter-spacing:10px;color:#1e1b4b;font-family:monospace;margin:0;">
      {{ $otp }}
    </p>
    <p style="font-size:12px;color:#94a3b8;margin-top:10px;">
      Expires in <strong style="color:#ef4444;">{{ $expiresInMinutes }} minutes</strong>
    </p>
  </div>
</div>

<p class="text">
  Enter this code on the verification page. It will expire automatically after {{ $expiresInMinutes }} minutes for your security.
</p>

<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px 18px;margin:20px 0;">
  <p style="font-size:13px;color:#b91c1c;font-weight:600;margin:0 0 4px;">
    🔒 Security Notice
  </p>
  <p style="font-size:13px;color:#991b1b;margin:0;line-height:1.5;">
    Never share this code with anyone. FeRa Clinic staff will never ask for it. If you did not attempt to sign in, please secure your account immediately.
  </p>
</div>

<div class="divider"></div>

<p style="font-size:12px;color:#94a3b8;">
  Sign-in attempt details:<br>
  IP Address: <strong>{{ request()->ip() }}</strong><br>
  Time: <strong>{{ now()->format('d M Y H:i:s') }} UTC</strong>
</p>
@endsection
