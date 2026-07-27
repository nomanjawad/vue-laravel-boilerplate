<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Reads static content from `data/*.json` for pages that don't need a DB.
 *
 * Caching behaviour (feedback.md §2):
 *   - In `app.debug` (local dev): NEVER cache. Editing data/home.json shows
 *     up on the next page load with zero manual `cache:clear`.
 *   - In production: cache is keyed on the file's mtime, so an edit + deploy
 *     invalidates automatically on the next request (old cache entries
 *     expire naturally). No manual `optimize:clear` needed for a content
 *     tweak.
 */
class JsonDataService
{
    public function get(string $filename): array
    {
        $path = base_path("data/{$filename}.json");

        // Debug builds: bypass cache entirely. `data/*.json` is content the
        // developer edits interactively; a 1-hour stale cache made it feel
        // like the file wasn't saved.
        if (config('app.debug')) {
            return $this->read($path);
        }

        // mtime in the key = automatic bust on file edit. Old keyed entries
        // expire on their own after `remember()`'s TTL — no explicit purge.
        $mtime = is_file($path) ? filemtime($path) : 0;
        $cacheKey = 'json_data_'.$filename.'_'.$mtime;

        // IMPORTANT: only cache plain arrays here — Laravel Collections/models
        // do not round-trip reliably through the file cache driver used on
        // shared hosting (v2 convention).
        return Cache::remember($cacheKey, 3600, function () use ($path) {
            return $this->read($path);
        });
    }

    private function read(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);

        return json_decode($content, true) ?? [];
    }

    public function clearCache(string $filename): void
    {
        // Legacy key format kept for backward compat; the current mtime-keyed
        // scheme doesn't strictly need explicit clears (edits change the key
        // automatically), but callers may still want to purge stale keys.
        Cache::forget('json_data_'.$filename);
    }

    public function clearAllCache(): void
    {
        $files = glob(base_path('data/*.json')) ?: [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            Cache::forget('json_data_'.$name);
        }
    }
}
