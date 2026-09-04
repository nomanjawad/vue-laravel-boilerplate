<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Theme settings → CSS custom properties for admin + public (synced).
 * Values live in site_settings (group `theme`); Setting model events bust
 * the response cache on change (Phase 0.1).
 */
final class Theme
{
    public const DEFAULT_FONT = 'instrument-sans';

    public const DEFAULT_RADIUS = 'md';

    /**
     * Curated bunny.net families. Key = slug used in settings + CSS URL;
     * value = CSS font-family name.
     *
     * @var array<string, string>
     */
    public const FONTS = [
        'instrument-sans' => 'Instrument Sans',
        'inter' => 'Inter',
        'dm-sans' => 'DM Sans',
        'plus-jakarta-sans' => 'Plus Jakarta Sans',
        'manrope' => 'Manrope',
        'source-sans-3' => 'Source Sans 3',
        'nunito-sans' => 'Nunito Sans',
        'space-grotesk' => 'Space Grotesk',
        'libre-franklin' => 'Libre Franklin',
        'playfair-display' => 'Playfair Display',
    ];

    /**
     * @var array<string, array{card: string, button: string}>
     */
    public const RADIUS = [
        'sm' => ['card' => '0.375rem', 'button' => '0.25rem'],
        'md' => ['card' => '0.75rem', 'button' => '0.5rem'],
        'lg' => ['card' => '1rem', 'button' => '0.75rem'],
    ];

    /**
     * @return array{primary: string, font: string, radius: string}
     */
    public static function settings(): array
    {
        $primary = BrandPalette::DEFAULT_PRIMARY;
        $font = self::DEFAULT_FONT;
        $radius = self::DEFAULT_RADIUS;

        try {
            if (! Schema::hasTable('site_settings')) {
                return compact('primary', 'font', 'radius');
            }
            $primary = BrandPalette::normalizeHex((string) Setting::get('theme_primary_color', $primary))
                ?? BrandPalette::DEFAULT_PRIMARY;
            $fontKey = (string) Setting::get('theme_font', $font);
            $font = array_key_exists($fontKey, self::FONTS) ? $fontKey : self::DEFAULT_FONT;
            $radiusKey = (string) Setting::get('theme_radius', $radius);
            $radius = array_key_exists($radiusKey, self::RADIUS) ? $radiusKey : self::DEFAULT_RADIUS;
        } catch (Throwable) {
            // DB down / mid-migrate — fall back to defaults (Inertia root still renders).
        }

        return compact('primary', 'font', 'radius');
    }

    /**
     * Inline :root rules emitted after @vite so they override @theme defaults.
     */
    public static function rootCss(): string
    {
        $s = self::settings();
        $palette = BrandPalette::fromHex($s['primary']);
        $fontName = self::FONTS[$s['font']];
        $radii = self::RADIUS[$s['radius']];

        $decls = [];
        foreach ($palette as $step => $hex) {
            $decls[] = "--color-brand-{$step}: {$hex}";
        }
        $stack = "'{$fontName}', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'";
        $decls[] = "--font-sans: {$stack}";
        $decls[] = "--font-display: '{$fontName}', ui-sans-serif, system-ui, sans-serif";
        $decls[] = "--radius-card: {$radii['card']}";
        $decls[] = "--radius-button: {$radii['button']}";

        return ':root{ '.implode('; ', $decls).'; }';
    }

    /**
     * bunny.net stylesheet when the chosen font is not the Vite-bundled default.
     * Preconnect to fonts.bunny.net is already in app.blade.php.
     */
    public static function bunnyStylesheetHref(): ?string
    {
        $font = self::settings()['font'];
        if ($font === self::DEFAULT_FONT) {
            return null;
        }

        // weights match vite.config.ts Instrument Sans set
        return 'https://fonts.bunny.net/css?family='.rawurlencode($font).':400,500,600&display=swap';
    }

    /**
     * Options for the Settings UI select.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function fontOptions(): array
    {
        $out = [];
        foreach (self::FONTS as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function radiusOptions(): array
    {
        return [
            ['value' => 'sm', 'label' => 'Small'],
            ['value' => 'md', 'label' => 'Medium'],
            ['value' => 'lg', 'label' => 'Large'],
        ];
    }
}
