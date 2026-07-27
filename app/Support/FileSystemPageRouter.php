<?php

namespace App\Support;

use App\Services\JsonDataService;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Next.js-style file-system routing for public pages.
 *
 * Convention:
 *   resources/js/Pages/Public/About/Index.vue        → GET /about
 *   resources/js/Pages/Public/CaseStudies/Index.vue  → GET /case-studies
 *   resources/js/Pages/Public/Home/Index.vue         → GET /             (special-cased)
 *
 * Every registered route renders the matching Inertia page and passes a
 * `data` prop resolved via JsonDataService (default key: kebab-cased folder
 * name; overridable per page). Explicit routes in routes/public.php ALWAYS
 * win — the auto-router only registers a URL if no GET route with that URI
 * has been claimed already. So all DB-bound public pages (Home, About, Blog,
 * Shop, Careers, CaseStudies, Contact, Profile, Page, sitemap, robots)
 * continue to use their explicit controllers unchanged.
 *
 * Optional per-page metadata sidecar at
 *   resources/js/Pages/Public/{Folder}/page.php
 * lets a page override any of `path`, `name`, `middleware`, `data`, `layout`:
 *
 *   <?php
 *   return [
 *       'path'       => '/about-us',                  // override kebab default
 *       'name'       => 'about',                      // route name
 *       'middleware' => ['responsecache'],            // extra middleware
 *       'data'       => 'about',                      // JsonDataService key
 *   ];
 *
 * If page.php is absent, everything is inferred from the folder name.
 *
 * Adding a new static public page is now:
 *   1. mkdir resources/js/Pages/Public/Pricing
 *   2. touch resources/js/Pages/Public/Pricing/Index.vue
 *   3. (optional) touch data/pricing.json
 * The URL /pricing responds immediately in dev; production requires
 * `php artisan route:cache` (or a fresh deploy).
 */
class FileSystemPageRouter
{
    public function __construct(private JsonDataService $jsonData) {}

    /**
     * Called from bootstrap/app.php after all explicit route files load.
     * Registers one route per discovered folder that isn't already claimed
     * by an explicit route. Idempotent — safe to call inside `route:cache`.
     */
    public function register(Router $router): void
    {
        $claimedUris = $this->claimedGetUris($router);

        foreach ($this->discover() as $config) {
            $uri = ltrim($config['path'], '/');
            if (in_array($uri, $claimedUris, true)) {
                continue;
            }

            $jsonKey = $config['data'];
            $component = $config['component'];

            $route = $router->get($config['path'], function () use ($component, $jsonKey) {
                return Inertia::render($component, [
                    'data' => $this->jsonData->get($jsonKey),
                ]);
            })->name($config['name']);

            if (! empty($config['middleware'])) {
                $route->middleware($config['middleware']);
            }
        }
    }

    /**
     * @return array<int, array{path: string, name: string, component: string, middleware: array<int, string>, data: string}>
     */
    public function discover(): array
    {
        $pagesDir = resource_path('js/Pages/Public');
        if (! is_dir($pagesDir)) {
            return [];
        }

        $configs = [];
        foreach (glob("{$pagesDir}/*", GLOB_ONLYDIR) ?: [] as $folder) {
            $folderName = basename($folder);
            $indexVue = "{$folder}/Index.vue";
            if (! file_exists($indexVue)) {
                continue;
            }

            $configs[] = $this->buildConfig($folderName, $folder);
        }

        return $configs;
    }

    /**
     * @return array{path: string, name: string, component: string, middleware: array<int, string>, data: string}
     */
    private function buildConfig(string $folderName, string $folderPath): array
    {
        $kebab = Str::kebab($folderName);

        $config = [
            'path' => $folderName === 'Home' ? '/' : "/{$kebab}",
            'name' => $kebab,
            'component' => "Public/{$folderName}/Index",
            'middleware' => [],
            'data' => $kebab,
        ];

        $sidecar = "{$folderPath}/page.php";
        if (file_exists($sidecar)) {
            $overrides = require $sidecar;
            if (is_array($overrides)) {
                $config = array_replace($config, $overrides);
            }
        }

        return $config;
    }

    /**
     * URIs (without leading slash) already registered as GET routes.
     * Anything in this list wins over auto-registration.
     *
     * @return array<int, string>
     */
    private function claimedGetUris(Router $router): array
    {
        $uris = [];
        foreach ($router->getRoutes() as $route) {
            if (in_array('GET', $route->methods(), true)) {
                $uris[] = ltrim($route->uri(), '/');
            }
        }

        return $uris;
    }
}
