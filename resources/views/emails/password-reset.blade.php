@php
    $siteName = \App\Models\Setting::query()->where('key', 'site_name')->value('value') ?? config('app.name');
@endphp
<x-mail::message-branded :subject="'Reset your password — ' . $siteName">
    <h1 style="margin:0 0 12px;font-size:20px;">Reset your password</h1>
    <p>You asked to reset the password on your <strong>{{ $siteName }}</strong> account.</p>
    <p style="margin:24px 0;">
        <a href="{{ $resetUrl }}" style="background:#4f46e5;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">
            Choose a new password
        </a>
    </p>
    <p style="color:#64748b;font-size:13px;">This link expires in {{ $expires ?? '60 minutes' }}. If you didn't ask for a password reset, you can safely ignore this email.</p>
</x-mail::message-branded>
