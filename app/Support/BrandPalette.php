<?php

namespace App\Support;

/**
 * Derive the 7 brand CSS steps (50,100,300,500,600,700,900) from a single
 * primary hex. Used by the Theme settings group → app.blade.php :root emit.
 *
 * Lightness clamp (300 / 500): brand text/links on the dark admin shell need
 * roughly L ≥ 45% (500) and L ≥ 55% (300). Darker primaries are lifted so
 * `text-brand-500` stays legible against `--admin-surface`. See the warning
 * block in resources/css/admin.css.
 */
final class BrandPalette
{
    /** Steps the @theme block / utilities expect. */
    public const STEPS = [50, 100, 300, 500, 600, 700, 900];

    /** Minimum lightness (0–1) for the 500 step on the dark admin shell. */
    private const MIN_L_500 = 0.45;

    /** Minimum lightness (0–1) for the 300 step. */
    private const MIN_L_300 = 0.55;

    public const DEFAULT_PRIMARY = '#6366f1';

    /**
     * @return array<int, string> step => #rrggbb
     */
    public static function fromHex(string $hex): array
    {
        $hex = self::normalizeHex($hex) ?? self::DEFAULT_PRIMARY;
        [$h, $s, $baseL] = self::hexToHsl($hex);

        $l500 = max($baseL, self::MIN_L_500);
        $l300 = max($l500 + 0.18, self::MIN_L_300);
        // Cap 300 below white so it still reads as a tint of the brand.
        $l300 = min($l300, 0.82);

        $targets = [
            50 => min($l500 + 0.42, 0.97),
            100 => min($l500 + 0.36, 0.93),
            300 => $l300,
            500 => $l500,
            600 => max($l500 - 0.08, 0.32),
            700 => max($l500 - 0.16, 0.26),
            900 => max($l500 - 0.32, 0.16),
        ];

        $out = [];
        foreach ($targets as $step => $l) {
            // Slightly desaturate the lightest tints so 50/100 don't look neon.
            $sat = $step <= 100 ? $s * 0.65 : ($step === 300 ? $s * 0.85 : $s);
            $out[$step] = self::hslToHex($h, min($sat, 1.0), $l);
        }

        return $out;
    }

    public static function normalizeHex(string $hex): ?string
    {
        $hex = trim($hex);
        if ($hex === '') {
            return null;
        }
        if ($hex[0] !== '#') {
            $hex = '#'.$hex;
        }
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $hex, $m)) {
            $c = $m[1];

            return sprintf('#%s%s%s%s%s%s', $c[0], $c[0], $c[1], $c[1], $c[2], $c[2]);
        }
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $hex)) {
            return strtolower($hex);
        }

        return null;
    }

    /**
     * @return array{0: float, 1: float, 2: float} H (0–360), S (0–1), L (0–1)
     */
    private static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d < 0.00001) {
            return [0.0, 0.0, $l];
        }

        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        switch ($max) {
            case $r:
                $h = (($g - $b) / $d) + ($g < $b ? 6 : 0);
                break;
            case $g:
                $h = (($b - $r) / $d) + 2;
                break;
            default:
                $h = (($r - $g) / $d) + 4;
                break;
        }

        return [$h * 60, $s, $l];
    }

    private static function hslToHex(float $h, float $s, float $l): string
    {
        $h = fmod($h, 360);
        if ($h < 0) {
            $h += 360;
        }

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0.0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0.0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0.0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0.0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0.0, $c];
        } else {
            [$r, $g, $b] = [$c, 0.0, $x];
        }

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        );
    }
}
