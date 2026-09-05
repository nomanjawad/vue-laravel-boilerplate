<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Build <link rel="preload" as="image"> attrs that mirror AppImage srcset
 * selection so the browser does not download a second candidate (F12 #2).
 */
final class LcpPreload
{
    /**
     * @return array{href: string, imagesrcset?: string, imagesizes?: string}|null
     */
    public static function fromMedia(mixed $raw, string $sizes = '100vw'): ?array
    {
        if (is_string($raw)) {
            $resolved = Media::imagePayload($raw);
            if (is_array($resolved)) {
                return self::fromMedia($resolved, $sizes);
            }
            $href = self::url($raw);

            return $href ? ['href' => $href] : null;
        }

        if (! is_array($raw)) {
            return null;
        }

        $url = $raw['url'] ?? null;
        if (! is_string($url) || $url === '') {
            return null;
        }

        $href = self::url($url);
        if ($href === null) {
            return null;
        }

        $variants = is_array($raw['variants'] ?? null) ? $raw['variants'] : [];
        $parts = [];

        foreach (['thumb' => 400, 'md' => 1200] as $name => $fallbackW) {
            $path = Media::variantPath($variants[$name] ?? null);
            if (! $path) {
                continue;
            }
            $u = self::url($path);
            if (! $u) {
                continue;
            }
            $w = is_array($variants[$name] ?? null) && isset($variants[$name]['width'])
                ? (int) $variants[$name]['width']
                : $fallbackW;
            $parts[] = "{$u} {$w}w";
        }

        $origW = isset($raw['width']) && (int) $raw['width'] > 0 ? (int) $raw['width'] : 2000;
        $parts[] = "{$href} {$origW}w";

        $out = ['href' => $href];
        if (count($parts) > 1) {
            $out['imagesrcset'] = implode(', ', $parts);
            $out['imagesizes'] = $sizes;
        }

        return $out;
    }

    private static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'uploads/')) {
            return '/'.$path;
        }

        return Storage::url($path);
    }
}
