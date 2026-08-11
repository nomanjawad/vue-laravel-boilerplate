<?php
// Admin > Custom Code snippets — injected verbatim into the public site only,
// never the admin panel (a customer's own tracking/verification scripts have
// no business running while an admin edits content). One query for all three
// placements; see App\Services\CustomCodeService.
$__customCode = request()->is('admin*')
    ? ['head' => '', 'body_start' => '', 'body_end' => '']
    : app(\App\Services\CustomCodeService::class)->renderAll();
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- laravel-vite-plugin's bunny() font helper (vite.config.ts) always loads
             from this origin — preconnecting saves the DNS+TLS handshake that would
             otherwise happen mid-render on the critical path. --}}
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        @unless(config('template.indexable'))
            <meta name="robots" content="noindex, nofollow">
        @endunless
        <title inertia>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
        {!! $__customCode['head'] !!}
    </head>
    <body class="font-sans antialiased">
        {!! $__customCode['body_start'] !!}
        @inertia
        {!! $__customCode['body_end'] !!}
    </body>
</html>
