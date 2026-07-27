<?php

/**
 * Content Security Policy — opt-in via CSP_ENABLED=true.
 *
 * When enabled, App\Http\Middleware\ContentSecurityPolicy builds a header
 * from the directives below and sets it on every response. Off by default
 * so a project using GA / Stripe / Cloudflare / etc. doesn't break the
 * moment CSP_ENABLED flips true — turn it on, watch the browser console,
 * add the hosts a real page needs, then ship.
 *
 * Nonce: script-src includes a per-request random nonce (added by the
 * middleware) so inline `<script>` blocks in app.blade.php remain
 * executable. Ziggy's inline script tag is one such block.
 */

return [
    'enabled' => (bool) env('CSP_ENABLED', false),

    // Report-only sets Content-Security-Policy-Report-Only instead of the
    // enforcing header — violations log to the console but nothing breaks.
    // Turn this on first while tuning directives.
    'report_only' => (bool) env('CSP_REPORT_ONLY', false),

    // Directives. Add project-specific hosts here (GA, Stripe, CDN, fonts).
    // 'self' covers same-origin. 'nonce' is expanded by the middleware.
    'directives' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'nonce'"],
        'style-src' => ["'self'", "'unsafe-inline'"], // Tailwind emits inline
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
        'connect-src' => ["'self'"],
        'frame-ancestors' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'object-src' => ["'none'"],
    ],
];
