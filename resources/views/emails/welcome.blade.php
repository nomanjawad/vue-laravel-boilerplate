@php
    $siteName = \App\Models\Setting::query()->where('key', 'site_name')->value('value') ?? config('app.name');
@endphp
<x-mail::message-branded :subject="'Welcome to ' . $siteName">
    <h1 style="margin:0 0 12px;font-size:20px;">Welcome, {{ $user->name }}!</h1>
    <p>Thanks for signing up at <strong>{{ $siteName }}</strong>. Your account is ready.</p>
    <p style="margin:24px 0;">
        <a href="{{ url('/login') }}" style="background:#4f46e5;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">
            Log in
        </a>
    </p>
    <p style="color:#64748b;font-size:13px;">If you didn't create this account, just ignore this email — no harm done.</p>
</x-mail::message-branded>
