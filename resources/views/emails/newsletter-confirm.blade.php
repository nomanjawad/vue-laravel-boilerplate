@php
    $siteName = \App\Models\Setting::query()->where('key', 'site_name')->value('value') ?? config('app.name');
@endphp
<x-mail::message-branded :subject="'You are subscribed — ' . $siteName">
    <h1 style="margin:0 0 12px;font-size:20px;">You're on the list 🎉</h1>
    <p>Thanks for subscribing to the {{ $siteName }} newsletter. We'll send the next issue to <strong>{{ $email }}</strong>.</p>
    @isset($unsubscribeUrl)
    <p style="color:#64748b;font-size:13px;margin-top:24px;">
        Don't want these any more? <a href="{{ $unsubscribeUrl }}" style="color:#64748b;">Unsubscribe</a>.
    </p>
    @endisset
</x-mail::message-branded>
