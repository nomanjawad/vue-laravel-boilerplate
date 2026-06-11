<?php

namespace App\Models;

use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

class Redirect extends Model
{
    use LogsContentActivity;

    public const CACHE_KEY = 'redirects.map';

    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $bust = function (): void {
            Cache::forget(self::CACHE_KEY);
            ResponseCache::clear();
        };

        static::saved($bust);
        static::deleted($bust);
    }

    /**
     * Cached from_path => [to_path, status_code] map used by the
     * HandleRedirects middleware on every request — keep it an array.
     */
    public static function map(): array
    {
        // IMPORTANT: only cache plain arrays here — Laravel Collections/models do not
        // round-trip reliably through the file cache driver used on shared hosting.
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return self::where('is_active', true)
                ->get(['from_path', 'to_path', 'status_code'])
                ->keyBy('from_path')
                ->map(fn ($r) => ['to' => $r->to_path, 'status' => (int) $r->status_code])
                ->toArray();
        });
    }

    /** Normalize a path for matching: leading slash, no trailing slash, no query. */
    public static function normalizePath(string $path): string
    {
        $path = '/'.trim(parse_url($path, PHP_URL_PATH) ?? '', '/');

        return $path === '' ? '/' : $path;
    }
}
