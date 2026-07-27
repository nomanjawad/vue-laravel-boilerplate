<!DOCTYPE html>
{{-- Zero-dependency fallback for database problems. Renders without Vite,
     Inertia, or the app boot chain finishing — the exception handler in
     bootstrap/app.php returns this directly when the app fails to reach
     the DB, so we get a helpful page instead of a raw PDOException stack. --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database problem</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 24px; }
        .card { max-width: 640px; width: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .code { display: inline-block; font-size: 12px; font-weight: 600; color: #4f46e5; background: #eef2ff; padding: 2px 8px; border-radius: 4px; letter-spacing: 0.05em; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 12px 0 8px; }
        p { color: #4b5563; line-height: 1.55; margin: 0 0 12px; }
        .hint { margin-top: 20px; padding: 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; }
        .hint p { color: #78350f; margin: 0 0 8px; }
        .hint code { background: #451a03; color: #fef3c7; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        details { margin-top: 20px; }
        summary { cursor: pointer; color: #6b7280; font-size: 13px; }
        pre { margin-top: 8px; padding: 12px; background: #111827; color: #e5e7eb; border-radius: 6px; font-size: 12px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="card">
        <span class="code">Database problem</span>
        <h1>{{ $title ?? 'The site can’t reach its database right now.' }}</h1>
        <p>{{ $body ?? 'This is usually temporary. If it persists, an administrator needs to check the database configuration.' }}</p>

        @if(!empty($hint))
        <div class="hint">
            <p><strong>Likely fix:</strong> {!! $hint !!}</p>
        </div>
        @endif

        @if(!empty($detail))
        <details>
            <summary>Technical detail (development only)</summary>
            <pre>{{ $detail }}</pre>
        </details>
        @endif
    </div>
</body>
</html>
