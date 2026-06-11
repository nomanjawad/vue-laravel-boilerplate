<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Busts every cached public page (spatie/laravel-responsecache) and the cached
 * sitemap whenever a content model changes. Apply to any model whose data is
 * rendered on cached public pages (posts, products, menus, settings, ...).
 */
trait ClearsResponseCache
{
    public static function bootClearsResponseCache(): void
    {
        static::saved(fn () => static::clearPublicCaches());
        static::deleted(fn () => static::clearPublicCaches());
    }

    protected static function clearPublicCaches(): void
    {
        ResponseCache::clear();
        Cache::forget('sitemap.xml');
    }
}
