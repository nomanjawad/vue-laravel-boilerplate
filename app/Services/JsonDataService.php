<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Spatie\ResponseCache\Facades\ResponseCache;

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
 *
 * Filenames may be a flat slug (`home`) or one subdirectory (`pages/home`).
 * `..` and deeper nesting are rejected.
 */
class JsonDataService
{
    /** Flat slug or one subdirectory: `pages/home`. Rejects `..`. */
    private const FILENAME_PATTERN = '/^[a-z0-9_-]+(\/[a-z0-9_-]+)?$/i';

    public function get(string $filename): array
    {
        $this->assertValidFilename($filename);

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

    /**
     * Overwrite `data/{filename}.json` with $data. Used by the admin Page
     * editor — never called from public read paths.
     *
     * $filename is restricted to a slug or one subdirectory (no `..`) so a
     * caller can't escape the `data/` directory. Written atomically (temp
     * file + rename) so a concurrent request never observes a half-written
     * file. PHP assoc arrays preserve insertion order on decode/encode, so
     * round-tripping request JSON through here keeps the original key order.
     */
    public function put(string $filename, array $data): void
    {
        $this->assertValidFilename($filename);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException("Failed to encode data/{$filename}.json: ".json_last_error_msg());
        }

        $path = base_path("data/{$filename}.json");
        File::ensureDirectoryExists(dirname($path));

        $tmpPath = $path.'.tmp-'.uniqid();

        file_put_contents($tmpPath, $json);
        rename($tmpPath, $path);

        // mtime-keyed json_data_* entries invalidate automatically; bust the
        // response cache + sitemap so public pages don't serve stale HTML for
        // up to 7 days after a green "saved" toast.
        ResponseCache::clear();
        SitemapService::forgetStatic();
    }

    /**
     * Delete `data/{filename}.json` if it exists. Busts response cache + sitemap.
     */
    public function delete(string $filename): void
    {
        $this->assertValidFilename($filename);

        $path = base_path("data/{$filename}.json");

        if (is_file($path)) {
            unlink($path);
        }

        ResponseCache::clear();
        SitemapService::forgetStatic();
    }

    /**
     * List JSON filenames (without `.json`) in `data/{dir}/`, or `data/` when
     * $dir is empty. Returns basename slugs only (e.g. `home`), not paths.
     *
     * @return list<string>
     */
    public function list(string $dir = ''): array
    {
        if ($dir !== '') {
            if (! preg_match('/^[a-z0-9_-]+$/i', $dir) || str_contains($dir, '..')) {
                throw new \InvalidArgumentException("Invalid data directory: {$dir}");
            }
        }

        $base = $dir === '' ? base_path('data') : base_path("data/{$dir}");

        if (! is_dir($base)) {
            return [];
        }

        $files = [];
        foreach (glob("{$base}/*.json") ?: [] as $path) {
            $files[] = pathinfo($path, PATHINFO_FILENAME);
        }

        sort($files);

        return $files;
    }

    private function assertValidFilename(string $filename): void
    {
        if (str_contains($filename, '..') || ! preg_match(self::FILENAME_PATTERN, $filename)) {
            throw new \InvalidArgumentException("Invalid data filename: {$filename}");
        }
    }
}
