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
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
