<?php

namespace App\Models\Concerns;

use App\Services\SitemapService;
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
        SitemapService::forgetStatic();
    }

    /**
     * Bust caches after query-builder / bulk writes that skip Eloquent events
     * (e.g. MenuController::reorder).
     */
    public static function bustPublicCaches(): void
    {
        static::clearPublicCaches();
    }
}
