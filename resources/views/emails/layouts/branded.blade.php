@php
    $siteName = \App\Models\Setting::query()->where('key', 'site_name')->value('value') ?? config('app.name');
    $supportEmail = \App\Models\Setting::query()->where('key', 'contact_email')->value('value') ?? config('mail.from.address');
    $brandColor = '#4f46e5';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $subject ?? $siteName }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,.06);" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="background:{{ $brandColor }};padding:20px 24px;color:#ffffff;">
                            <strong style="font-size:18px;">{{ $siteName }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 24px;line-height:1.6;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background:#f8fafc;color:#64748b;font-size:12px;border-top:1px solid #e2e8f0;">
                            Sent by {{ $siteName }} &middot; <a href="mailto:{{ $supportEmail }}" style="color:#64748b;">{{ $supportEmail }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
