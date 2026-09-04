<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Core\ModuleManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Per-layer cache panel. Deliberately never calls Cache::flush() — that would
 * also wipe the spatie permission cache and rate-limiter counters.
 */
class CacheController extends Controller
{
    public const LAYERS = [
        'pages' => [
            'label' => 'Page cache',
            'description' => 'Full-page HTML responses (spatie/responsecache). Public pages may stay stale up to 7 days until this is cleared.',
        ],
        'sitemap' => [
            'label' => 'Sitemap',
            'description' => 'Cached /sitemap.xml body (24h TTL).',
        ],
        'settings' => [
            'label' => 'Settings',
            'description' => 'Cached site_settings pluck (1h TTL).',
        ],
        'modules' => [
            'label' => 'Modules registry',
            'description' => 'Cached module enablement / nav registry.',
        ],
        'redirects' => [
            'label' => 'Redirects',
            'description' => 'Cached 301/302 map used by HandleRedirects.',
        ],
        'views' => [
            'label' => 'Compiled views',
            'description' => 'Blade compiled view cache (view:clear).',
        ],
    ];

    public function index()
    {
        $layers = [];
        foreach (self::LAYERS as $key => $meta) {
            $layers[] = [
                'key' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'last_cleared_at' => Cache::get($this->timestampKey($key)),
            ];
        }

        return Inertia::render('Admin/System/Cache', [
            'layers' => $layers,
            'last_cleared_all_at' => Cache::get($this->timestampKey('all')),
        ]);
    }

    /**
     * Clear one layer, or every managed layer when $layer === 'all'.
     * Also used by the dashboard "Clear page cache" shortcut (layer=pages).
     */
    public function clear(string $layer = 'pages')
    {
        $allowed = array_merge(array_keys(self::LAYERS), ['all']);
        abort_unless(in_array($layer, $allowed, true), 404);

        if ($layer === 'all') {
            foreach (array_keys(self::LAYERS) as $key) {
                $this->clearLayer($key);
            }
            Cache::forever($this->timestampKey('all'), now()->toIso8601String());

            return back()->with('success', 'All managed caches cleared.');
        }

        $this->clearLayer($layer);
        $label = self::LAYERS[$layer]['label'];

        return back()->with('success', "{$label} cleared.");
    }

    protected function clearLayer(string $layer): void
    {
        match ($layer) {
            'pages' => ResponseCache::clear(),
            'sitemap' => Cache::forget('sitemap.xml'),
            'settings' => Cache::forget('site_settings'),
            'modules' => app(ModuleManager::class)->forgetCache(),
            'redirects' => Cache::forget('redirects.map'),
            'views' => Artisan::call('view:clear'),
            default => null,
        };

        Cache::forever($this->timestampKey($layer), now()->toIso8601String());
    }

    protected function timestampKey(string $layer): string
    {
        return "cache_cleared.{$layer}";
    }
}
