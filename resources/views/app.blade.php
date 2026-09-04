<?php
// Admin > Custom Code snippets — injected verbatim into the public site only,
// never the admin panel (a customer's own tracking/verification scripts have
// no business running while an admin edits content). One query for all three
// placements; see App\Services\CustomCodeService.
$__customCode = request()->is('admin*')
    ? ['head' => '', 'body_start' => '', 'body_end' => '']
    : app(\App\Services\CustomCodeService::class)->renderAll();

// Favicon from site settings (PUBLIC_SETTINGS). Fall back to /favicon.ico.
$__favicon = '';
try {
    if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
        $__favicon = (string) (\App\Models\Setting::get('site_favicon') ?: '');
    }
} catch (\Throwable) {
    $__favicon = '';
}
if ($__favicon !== '' && ! str_starts_with($__favicon, 'http') && ! str_starts_with($__favicon, '/')) {
    $__favicon = str_starts_with($__favicon, 'uploads/') ? '/'.$__favicon : '/storage/'.$__favicon;
}

// Theme tokens (Phase 8) — brand palette, font, radius. Cached via Setting::get.
$__themeCss = \App\Support\Theme::rootCss();
$__themeFontHref = \App\Support\Theme::bunnyStylesheetHref();
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- laravel-vite-plugin's bunny() font helper (vite.config.ts) always loads
             from this origin — preconnecting saves the DNS+TLS handshake that would
             otherwise happen mid-render on the critical path. Also used when Theme
             settings pick a non-default bunny font. --}}
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        @if($__favicon)
            <link rel="icon" href="{{ $__favicon }}">
        @else
            <link rel="icon" href="/favicon.ico">
        @endif
        @unless(config('template.indexable'))
            <meta name="robots" content="noindex, nofollow">
        @endunless
        <title inertia>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        {{-- Override @theme defaults after Vite CSS so admin + public stay in sync. --}}
        @if($__themeFontHref)
            <link rel="stylesheet" href="{{ $__themeFontHref }}">
        @endif
        <style>{!! $__themeCss !!}</style>
        @inertiaHead
        {!! $__customCode['head'] !!}
    </head>
    <body class="font-sans antialiased">
        {!! $__customCode['body_start'] !!}
        @inertia
        {!! $__customCode['body_end'] !!}
    </body>
</html>
