<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use ClearsResponseCache;
    use HasFactory;

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
