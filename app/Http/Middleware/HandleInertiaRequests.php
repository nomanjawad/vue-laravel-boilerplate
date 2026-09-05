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
use App\Models\Category;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Setting;
use App\Modules\Core\ModuleManager;
use App\Services\JsonDataService;
use App\Services\SeoService;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
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
            self::$tablesExist = SchemaCache::hasTable('menus') && SchemaCache::hasTable('site_settings');
        }
        $tablesExist = self::$tablesExist;
        $isAdmin = $request->is('admin', 'admin/*');

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

            // Admin sidebar only — skip nav/badge work on public requests (F12 #4).
            'modules' => fn () => $isAdmin
                ? ModulesSharedData::from([
                    'nav' => array_map(
                        fn (array $entry) => ModuleNavEntry::from($entry),
                        $this->modules->navFor($permissionNames, $isSuperAdmin),
                    ),
                    'enabled' => collect($this->modules->manifests())
                        ->filter(fn ($_, $k) => $this->modules->enabled($k))
                        ->keys()
                        ->values()
                        ->toArray(),
                ])->toArray()
                : ModulesSharedData::from([
                    'nav' => [],
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
                    'header' => $this->menuTree('header'),
                    'footer' => $this->menuTree('footer'),
                ])->toArray()
                : MenusData::from(['header' => [], 'footer' => []])->toArray(),

            // header.json + footer.json — public pages only (admin has its own editor).
            'layout' => fn () => $isAdmin
                ? null
                : [
                    'header' => $this->jsonData->get('header'),
                    'footer' => $this->jsonData->get('footer'),
                ],

            'settings' => fn () => $tablesExist
                ? SettingsData::from($this->publicSettingsMap())->toArray()
                : SettingsData::from([])->toArray(),

            // Resolved logo for AppImage (srcset/dims) — public header CLS (F12 #3).
            'siteLogo' => fn () => $isAdmin || ! $tablesExist
                ? null
                : $this->resolveSiteLogo(),

            'enabledFeatures' => config('template.features'),

            'seo' => fn () => $this->resolveSeo($request, $tablesExist),

            // Organization (+ optional LocalBusiness) JSON-LD on every page;
            // pages add their own schemas (BlogPosting, FAQPage, breadcrumbs,
            // JobPosting) via a `jsonLd` prop.
            'organizationJsonLd' => fn () => $tablesExist ? $this->seo->organization() : null,
            'localBusinessJsonLd' => fn () => $tablesExist ? $this->seo->localBusiness() : null,
        ];
    }

    /**
     * Whitelisted public settings via the cached Setting::get map (F12 #4).
     *
     * @return array<string, mixed>
     */
    protected function publicSettingsMap(): array
    {
        $out = [];
        foreach (self::PUBLIC_SETTINGS as $key) {
            $out[$key] = Setting::get($key);
        }

        return $out;
    }

    /**
     * @return array{url: string, variants: ?array, width: ?int, height: ?int, alt_text: ?string}|string|null
     */
    protected function resolveSiteLogo(): array|string|null
    {
        $fromSettings = Setting::get('site_logo');
        if (is_string($fromSettings) && $fromSettings !== '') {
            return Media::imagePayload($fromSettings);
        }

        $header = $this->jsonData->get('header');
        $fromHeader = is_string($header['logo'] ?? null) ? $header['logo'] : null;

        return Media::imagePayload($fromHeader);
    }

    /**
     * Active roots for a menu location, each with active children (depth ≤ 2).
     *
     * @return list<MenuItemData>
     */
    protected function menuTree(string $location): array
    {
        return Menu::where('location', $location)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'title', 'url', 'sort_order', 'parent_id'])
            ->map(fn (Menu $menu) => MenuItemData::fromMenu($menu))
            ->values()
            ->all();
    }

    /**
     * Build the SEO meta payload for the current route.
     *
     * Canonical / OG tags are always derived (never overridden from the
     * editor). See feedback.md F1 / F2 / F11 #10.
     */
    protected function resolveSeo(Request $request, bool $settingsExist): array
    {
        $siteName = $settingsExist
            ? ((string) Setting::get('site_name') ?: (string) config('app.name'))
            : (string) config('app.name');
        $defaultDescription = $settingsExist ? (string) (Setting::get('site_description') ?: '') : '';
        $defaultImage = $settingsExist ? Setting::get('og_image') : null;
        $titleTemplate = $settingsExist
            ? ((string) (Setting::get('seo_title_template') ?: '%title% — %site_name%'))
            : '%title% — %site_name%';
        $siteNoindex = $settingsExist && Setting::get('site_noindex') === '1';

        $routeName = $request->route()?->getName();
        $meta = [];
        $contentTitle = '';
        $featuredImage = '';
        $ogType = 'website';
        $articlePublished = null;
        $articleModified = null;

        $slug = match ($routeName) {
            'home' => 'home',
            'page.show' => $request->route('slug'),
            default => null,
        };

        if (is_string($slug) && $slug !== '') {
            $page = $this->jsonData->get("pages/{$slug}");
            $meta = is_array($page['seo'] ?? null) ? $page['seo'] : [];
            $contentTitle = (string) ($page['title'] ?? $slug);
            // featured_image is the source of og:image; legacy seo.og_image fallback.
            $featuredImage = (string) ($page['featured_image'] ?? $meta['og_image'] ?? '');
        }

        if ($routeName === 'blog.show') {
            $post = $request->route('post');
            if ($post instanceof Post) {
                $meta = [
                    'title' => $post->meta_title ?: '',
                    'description' => $post->meta_description ?: ($post->excerpt ?: ''),
                    'noindex' => (bool) $post->noindex,
                ];
                $contentTitle = $post->title;
                $featuredImage = (string) ($post->featured_image ?: '');
                $ogType = 'article';
                $articlePublished = $post->published_at?->toAtomString();
                $articleModified = $post->updated_at?->toAtomString();
            }
        }

        if ($routeName === 'blog.category') {
            $category = $request->route('category');
            if ($category instanceof Category) {
                $meta = [
                    'title' => '',
                    'description' => $category->description ?: '',
                ];
                $contentTitle = $category->name;
            }
        }

        if ($routeName === 'blog.index') {
            $contentTitle = 'Blog';
        }

        if (in_array($routeName, ['careers.index', 'careers.show'], true)) {
            $career = $request->route('career');
            $contentTitle = is_object($career) && isset($career->title)
                ? (string) $career->title
                : 'Careers';
        }

        if (in_array($routeName, ['case-studies.index', 'case-studies.show'], true)) {
            $study = $request->route('caseStudy') ?? $request->route('case_study');
            $contentTitle = is_object($study) && isset($study->title)
                ? (string) $study->title
                : 'Case Studies';
            if (is_object($study) && isset($study->featured_image)) {
                $featuredImage = (string) $study->featured_image;
            }
        }

        $metaTitle = (string) ($meta['title'] ?? '');
        $description = (string) ($meta['description'] ?? '');
        $jsonLd = (string) ($meta['json_ld'] ?? '');

        $resolvedTitle = $this->seo->applyTitleTemplate(
            $metaTitle,
            $contentTitle !== '' ? $contentTitle : $siteName,
            $siteName,
            $titleTemplate,
        );

        $resolvedDescription = $description !== '' ? $description : $defaultDescription;

        // Derived OG: always mirror resolved meta + featured image (no overrides).
        $ogImage = $this->seo->absoluteUrl($featuredImage !== '' ? $featuredImage : $defaultImage);

        return SeoData::from([
            'site_name' => $siteName,
            'title' => $resolvedTitle !== '' ? $resolvedTitle : null,
            'description' => $resolvedDescription,
            'og_image' => $ogImage,
            'og_title' => $resolvedTitle !== '' ? $resolvedTitle : null,
            'og_description' => $resolvedDescription !== '' ? $resolvedDescription : null,
            'og_type' => $ogType,
            'twitter_card' => 'summary_large_image',
            'article_published_time' => $articlePublished,
            'article_modified_time' => $articleModified,
            'canonical' => $this->seo->canonicalUrl($request),
            'noindex' => ! config('template.indexable') || $siteNoindex || (bool) ($meta['noindex'] ?? false),
            'json_ld' => $jsonLd !== '' ? $jsonLd : null,
        ])->toArray();
    }
}
