<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\Setting;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private SeoService $seo)
    {
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $tablesExist = Schema::hasTable('menus') && Schema::hasTable('site_settings');

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],

            'menus' => $tablesExist ? [
                'header' => Menu::where('location', 'header')
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get(['id', 'title', 'url', 'sort_order']),
                'footer' => Menu::where('location', 'footer')
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->get(['id', 'title', 'url', 'sort_order']),
            ] : ['header' => [], 'footer' => []],

            'settings' => $tablesExist
                ? Setting::pluck('value', 'key')->toArray()
                : [],

            'enabledFeatures' => config('template.features'),

            'cartCount' => fn () => count($request->session()->get('cart', [])),

            'seo' => fn () => $this->resolveSeo($request, $tablesExist),
        ];
    }

    /**
     * Build the SEO meta payload for the current route: per-route overrides
     * from the page_metas table, falling back to global site settings.
     */
    protected function resolveSeo(Request $request, bool $settingsExist): array
    {
        $settings = $settingsExist ? Setting::pluck('value', 'key') : collect();
        $siteName = $settings->get('site_name') ?: config('app.name', 'WebTemplate');
        $defaultDescription = $settings->get('site_description') ?: '';
        $defaultImage = $settings->get('og_image');

        $routeName = $request->route()?->getName();
        $meta = ($routeName && Schema::hasTable('page_metas'))
            ? $this->seo->getMetaForRoute($routeName)
            : [];

        return [
            'site_name'   => $siteName,
            'title'       => $meta['title'] ?? null,
            'description' => $meta['description'] ?? $defaultDescription,
            'og_image'    => $meta['og_image'] ?? $defaultImage,
            'canonical'   => $request->url(),
        ];
    }
}
