<?php

namespace App\Http\Middleware;

use App\Data\AuthData;
use App\Data\FlashData;
use App\Data\MenuItemData;
use App\Data\MenusData;
use App\Data\ModuleNavEntry;
use App\Data\ModulesSharedData;
use App\Data\SeoData;
use App\Data\SettingsData;
use App\Models\Menu;
use App\Models\Setting;
use App\Modules\Core\ModuleManager;
use App\Services\JsonDataService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * !! WARNING — every key listed here is exposed to EVERY visitor's browser. !!
     *
     * The site_settings table may hold secrets (SMTP passwords, API/recaptcha
     * secret keys, payment credentials). Never share the whole table; only add
     * a key to this whitelist if it is safe to print on a public web page.
     */
    private const PUBLIC_SETTINGS = [
        'site_name',
        'site_description',
        'site_logo',
        'site_favicon',
        'og_image',
        'contact_email',
        'contact_phone',
        'address',
        'whatsapp',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'shop_location',
        'shop_currency',
        'shop_currency_symbol',
        // Analytics ids are public by nature (visible in page source).
        'ga_measurement_id',
        'gtm_container_id',
        // Cookie-consent banner copy.
        'cookie_consent_text',
    ];

    protected $rootView = 'app';

    /** Memoized per-process so we don't hit `information_schema` on every request. */
    private static ?bool $tablesExist = null;

    public function __construct(
        private SeoService $seo,
        private ModuleManager $modules,
        private JsonDataService $jsonData,
    ) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        if (self::$tablesExist === null) {
            self::$tablesExist = Schema::hasTable('menus') && Schema::hasTable('site_settings');
        }
        $tablesExist = self::$tablesExist;

        // Eager-load roles + permissions once per request so `auth`/`modules`
        // shares don't each re-query Spatie's tables.
        $user = $request->user();
        if ($user) {
            $user->loadMissing('roles.permissions', 'permissions');
        }
        $permissionNames = $user?->getAllPermissions()->pluck('name')->toArray() ?? [];
        $isSuperAdmin = (bool) $user?->hasRole('super-admin');

        return [
            ...parent::share($request),

            'auth' => fn () => AuthData::fromUser($user, $permissionNames, $isSuperAdmin)->toArray(),

            // Module registry — sidebar uses this; pages can read it via
            // useModule() to gracefully handle "module disabled while page open".
            'modules' => fn () => ModulesSharedData::from([
                'nav' => array_map(
                    fn (array $entry) => ModuleNavEntry::from($entry),
                    $this->modules->navFor($permissionNames, $isSuperAdmin),
                ),
                'enabled' => collect($this->modules->manifests())
                    ->filter(fn ($_, $k) => $this->modules->enabled($k))
                    ->keys()
                    ->values()
                    ->toArray(),
            ])->toArray(),

            'flash' => fn () => FlashData::from([
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                // MediaController::store flashes a MediaData here; without
                // forwarding it, AppMediaPicker's flash.media read is always null.
                'media' => $request->session()->get('media'),
            ])->toArray(),

            'menus' => fn () => $tablesExist
                ? MenusData::from([
                    'header' => Menu::where('location', 'header')
                        ->where('is_active', true)
                        ->whereNull('parent_id')
                        ->orderBy('sort_order')
                        ->get(['id', 'title', 'url', 'sort_order'])
                        ->map(fn (Menu $menu) => MenuItemData::from($menu))
                        ->values()
                        ->all(),
                    'footer' => Menu::where('location', 'footer')
                        ->where('is_active', true)
                        ->whereNull('parent_id')
                        ->orderBy('sort_order')
                        ->get(['id', 'title', 'url', 'sort_order'])
                        ->map(fn (Menu $menu) => MenuItemData::from($menu))
                        ->values()
                        ->all(),
                ])->toArray()
                : MenusData::from(['header' => [], 'footer' => []])->toArray(),

            'settings' => fn () => $tablesExist
                ? SettingsData::from(
                    Setting::whereIn('key', self::PUBLIC_SETTINGS)->pluck('value', 'key')->toArray()
                )->toArray()
                : SettingsData::from([])->toArray(),

            'enabledFeatures' => config('template.features'),

            'cartCount' => fn () => count($request->session()->get('cart', [])),

            'seo' => fn () => $this->resolveSeo($request, $tablesExist),

            // Organization JSON-LD on every page; pages add their own schemas
            // (Article, JobPosting, breadcrumbs) via a `jsonLd` prop.
            'organizationJsonLd' => fn () => $tablesExist ? $this->seo->organization() : null,
        ];
    }

    /** Route name → data/*.json file, for pages whose SEO block is admin-editable. */
    private const SEO_PAGE_MAP = [
        'home' => 'home',
        'about' => 'about',
        'contact' => 'contact',
    ];

    /**
     * Build the SEO meta payload for the current route: the `seo` block
     * inside the matching page's data/*.json (edited via the admin Page
     * Content panel), falling back to global site settings.
     */
    protected function resolveSeo(Request $request, bool $settingsExist): array
    {
        $settings = $settingsExist
            ? Setting::whereIn('key', ['site_name', 'site_description', 'og_image'])->pluck('value', 'key')
            : collect();
        // No literal brand fallback — an empty site_name + empty APP_NAME
        // should surface as an obvious blank in the tab title so the project
        // owner notices during setup, not ship a placeholder to visitors.
        $siteName = $settings->get('site_name') ?: (string) config('app.name');
        $defaultDescription = $settings->get('site_description') ?: '';
        $defaultImage = $settings->get('og_image');

        $routeName = $request->route()?->getName();
        $jsonFile = self::SEO_PAGE_MAP[$routeName] ?? null;
        $meta = $jsonFile ? ($this->jsonData->get($jsonFile)['seo'] ?? []) : [];

        // `?? '' | ?:` in two steps, not a bare `$meta['x'] ?: …`: `??` on the
        // array read is what avoids an "undefined array key" warning when the
        // route has no seo block (or the block omits a key); the outer `?:`
        // then treats an admin-cleared field ("" — the JSON editor writes
        // empty strings, not null) the same as a missing one.
        $title = $meta['title'] ?? '';
        $description = $meta['description'] ?? '';
        $ogImage = $meta['og_image'] ?? '';

        // og:image must be an absolute URL for social crawlers. Media URLs are
        // stored root-relative (see Media::getUrlAttribute), so promote a
        // relative value to absolute here; an already-absolute URL is untouched.
        $ogImage = $ogImage ?: $defaultImage;
        if ($ogImage && ! preg_match('#^https?://#', $ogImage)) {
            $ogImage = url($ogImage);
        }

        return SeoData::from([
            'site_name' => $siteName,
            'title' => $title ?: null,
            'description' => $description ?: $defaultDescription,
            'og_image' => $ogImage,
            'canonical' => $request->url(),
        ])->toArray();
    }
}
