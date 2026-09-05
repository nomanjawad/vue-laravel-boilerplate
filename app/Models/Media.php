<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use ClearsResponseCache;
    use HasFactory;
    use LogsContentActivity;

    /** @var string */
    protected string $activityLogName = 'media';

    protected $fillable = [
        'user_id', 'filename', 'path', 'mime_type', 'size',
        'width', 'height', 'alt_text', 'disk', 'variants',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        // Prefer a root-relative URL for app-origin assets. Storage's public-disk
        // URL bakes in APP_URL, which breaks <img> previews whenever the app is
        // served on a different host/port than APP_URL (e.g. `php artisan serve`
        // on :8001, Herd, a reverse proxy) or when a CSP only allows img-src
        // 'self'. A leading-slash path loads from whatever origin the page is on.
        // Consumers that need an absolute URL (og:image, sitemap, feeds) promote
        // it with url() at the point of use.
        //
        // Only strip the origin when it matches APP_URL. If the public disk is
        // pointed at S3/a CDN, url() returns a genuinely external absolute URL
        // that must be left intact.
        $url = Storage::disk($this->disk)->url($this->path);

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);

        if ($urlHost !== null && $urlHost === $appHost) {
            return preg_replace('#^https?://[^/]+#', '', $url) ?? $url;
        }

        return $url;
    }

    /**
     * Extract a storage path from a variants JSON entry.
     * Supports legacy string paths and the Phase-10 shape
     * `{path, width, height}`.
     */
    public static function variantPath(mixed $entry): ?string
    {
        if (is_string($entry) && $entry !== '') {
            return $entry;
        }

        if (is_array($entry) && isset($entry['path']) && is_string($entry['path']) && $entry['path'] !== '') {
            return $entry['path'];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function variantPaths(): array
    {
        $paths = [];
        foreach ($this->variants ?? [] as $entry) {
            $path = self::variantPath($entry);
            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * AppImage-compatible payload from a stored URL/path (featured_image, photo…).
     * Falls back to a plain URL string when the media row is missing (imports).
     *
     * @return array{url: string, variants: ?array, width: ?int, height: ?int, alt_text: ?string}|string|null
     */
    public static function imagePayload(?string $stored): array|string|null
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $media = self::findByStoredUrl($stored);
        if ($media) {
            return $media->toImagePayload();
        }

        return $stored;
    }

    /**
     * Batch-resolve stored URLs → AppImage payloads (one query).
     *
     * @param  iterable<string|null>  $storedUrls
     * @return array<string, array{url: string, variants: ?array, width: ?int, height: ?int, alt_text: ?string}|string>
     */
    public static function imagePayloadMap(iterable $storedUrls): array
    {
        $unique = [];
        foreach ($storedUrls as $url) {
            if (is_string($url) && $url !== '') {
                $unique[$url] = true;
            }
        }

        if ($unique === []) {
            return [];
        }

        $paths = [];
        foreach (array_keys($unique) as $url) {
            $path = self::storagePathFromStoredUrl($url);
            if ($path !== null) {
                $paths[$path] = $url;
            }
        }

        $byPath = $paths === []
            ? collect()
            : self::query()->whereIn('path', array_keys($paths))->get()->keyBy('path');

        $out = [];
        foreach (array_keys($unique) as $url) {
            $path = self::storagePathFromStoredUrl($url);
            $media = $path !== null ? $byPath->get($path) : null;
            $out[$url] = $media instanceof self ? $media->toImagePayload() : $url;
        }

        return $out;
    }

    /**
     * @return array{url: string, variants: ?array, width: ?int, height: ?int, alt_text: ?string}
     */
    public function toImagePayload(): array
    {
        return [
            'url' => $this->url,
            'variants' => $this->variants,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->alt_text,
        ];
    }

    public static function findByStoredUrl(?string $stored): ?self
    {
        $path = self::storagePathFromStoredUrl($stored);
        if ($path === null) {
            return null;
        }

        return self::query()->where('path', $path)->first();
    }

    /**
     * Normalize a browser URL or disk path to the media.path column value.
     */
    public static function storagePathFromStoredUrl(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $path = parse_url($stored, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $stored;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        // Public imports under /uploads/ are not media-library rows.
        if (str_starts_with($path, 'uploads/')) {
            return null;
        }

        return $path !== '' ? $path : null;
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
        ];
    }
}
