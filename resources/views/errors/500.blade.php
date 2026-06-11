<!DOCTYPE html>
{{-- Zero-dependency fallback: must render even when Vite manifest or the DB is broken. --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; color: #111827; display: flex; min-height: 100vh; align-items: center; justify-content: center; text-align: center; }
        .code { font-size: 72px; font-weight: 700; color: #4f46e5; margin: 0; }
        h1 { font-size: 24px; margin: 16px 0 8px; }
        p { color: #4b5563; max-width: 420px; margin: 0 auto; }
        a { display: inline-block; margin-top: 32px; background: #4f46e5; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; }
    </style>
</head>
<body>
    <div>
        <p class="code">500</p>
        <h1>Server Error</h1>
        <p>Something went wrong on our end. Please try again in a few minutes.</p>
        <a href="/">Back to home</a>
    </div>
</body>
</html>
