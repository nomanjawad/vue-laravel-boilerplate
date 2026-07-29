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
        @unless(config('template.indexable'))
            <meta name="robots" content="noindex, nofollow">
        @endunless
        <title inertia>{{ config('app.name') }}</title>
        {{-- Ziggy: emits window.Ziggy for the standalone `route()` import in
             composables and layouts. Without it, `import { route } from
             'ziggy-js'` throws "Cannot read properties of undefined". --}}
        @routes
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
