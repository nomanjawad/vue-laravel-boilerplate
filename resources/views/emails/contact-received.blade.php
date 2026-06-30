@php
    $siteName = \App\Models\Setting::query()->where('key', 'site_name')->value('value') ?? config('app.name');
@endphp
<x-mail::message-branded :subject="'New contact form submission — ' . $siteName">
    <h1 style="margin:0 0 12px;font-size:20px;">New contact submission</h1>
    <table cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">
        <tr><td style="color:#64748b;padding:4px 8px 4px 0;">From</td><td>{{ $name }} &lt;{{ $email }}&gt;</td></tr>
        @if(!empty($phone))<tr><td style="color:#64748b;padding:4px 8px 4px 0;">Phone</td><td>{{ $phone }}</td></tr>@endif
        @if(!empty($subjectLine))<tr><td style="color:#64748b;padding:4px 8px 4px 0;">Subject</td><td>{{ $subjectLine }}</td></tr>@endif
    </table>
    <div style="margin-top:16px;padding:12px;background:#f8fafc;border-radius:6px;white-space:pre-wrap;">{{ $body }}</div>
</x-mail::message-branded>
