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
use App\Models\Menu;
use App\Models\Post;
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
                    'header' => $this->menuTree('header'),
                    'footer' => $this->menuTree('footer'),
                ])->toArray()
                : MenusData::from(['header' => [], 'footer' => []])->toArray(),

            // header.json + footer.json — public pages only (admin has its own editor).
            'layout' => fn () => $request->is('admin', 'admin/*')
                ? null
                : [
                    'header' => $this->jsonData->get('header'),
                    'footer' => $this->jsonData->get('footer'),
                ],

            'settings' => fn () => $tablesExist
                ? SettingsData::from(
                    Setting::whereIn('key', self::PUBLIC_SETTINGS)->pluck('value', 'key')->toArray()
                )->toArray()
                : SettingsData::from([])->toArray(),

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
     * Sources (in priority order for content):
     * - JSON page `seo` block (home / dynamic pages)
     * - Post columns (blog.show)
     * - Category name/description (blog.category)
     * - Fallback content titles for index routes (Blog, Careers, …)
     *
     * Title template (`seo_title_template`, default `%title% — %site_name%`)
     * applies only when the page/post has no explicit meta title. Canonical is
     * always absolute via `url()`; an optional override field wins when set.
     */
    protected function resolveSeo(Request $request, bool $settingsExist): array
    {
        $settings = $settingsExist
            ? Setting::whereIn('key', [
                'site_name', 'site_description', 'og_image', 'site_noindex', 'seo_title_template',
            ])->pluck('value', 'key')
            : collect();
        // No literal brand fallback — an empty site_name + empty APP_NAME
        // should surface as an obvious blank in the tab title so the project
        // owner notices during setup, not ship a placeholder to visitors.
        $siteName = $settings->get('site_name') ?: (string) config('app.name');
        $defaultDescription = $settings->get('site_description') ?: '';
        $defaultImage = $settings->get('og_image');
        $titleTemplate = $settings->get('seo_title_template') ?: '%title% — %site_name%';
        // Sitewide kill switch (Admin > Settings > SEO & Analytics) — forces
        // noindex on every route, composed with (never overridden by) a
        // page's own noindex below.
        $siteNoindex = $settings->get('site_noindex') === '1';

        $routeName = $request->route()?->getName();
        $meta = [];
        $contentTitle = '';
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
        }

        if ($routeName === 'blog.show') {
            $post = $request->route('post');
            if ($post instanceof Post) {
                $meta = [
                    'title' => $post->meta_title ?: '',
                    'description' => $post->meta_description ?: ($post->excerpt ?: ''),
                    'og_image' => $post->og_image ?: ($post->featured_image ?: ''),
                    'og_title' => $post->og_title ?: '',
                    'og_description' => $post->og_description ?: '',
                    'canonical' => $post->canonical_url ?: '',
                    'noindex' => (bool) $post->noindex,
                ];
                $contentTitle = $post->title;
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
        }

        // `?? '' | ?:` in two steps, not a bare `$meta['x'] ?: …`: `??` on the
        // array read is what avoids an "undefined array key" warning when the
        // route has no seo block (or the block omits a key); the outer `?:`
        // then treats an admin-cleared field ("" — the JSON editor writes
        // empty strings, not null) the same as a missing one.
        $metaTitle = (string) ($meta['title'] ?? '');
        $description = (string) ($meta['description'] ?? '');
        $ogImage = (string) ($meta['og_image'] ?? '');
        $ogTitle = (string) ($meta['og_title'] ?? '');
        $ogDescription = (string) ($meta['og_description'] ?? '');
        $canonicalOverride = (string) ($meta['canonical'] ?? '');
        $jsonLd = (string) ($meta['json_ld'] ?? '');

        $resolvedTitle = $this->seo->applyTitleTemplate(
            $metaTitle,
            $contentTitle !== '' ? $contentTitle : $siteName,
            $siteName,
            $titleTemplate,
        );

        // og:image must be an absolute URL for social crawlers. Media URLs are
        // stored root-relative (see Media::getUrlAttribute), so promote a
        // relative value to absolute here; an already-absolute URL is untouched.
        $ogImage = $this->seo->absoluteUrl($ogImage ?: $defaultImage);

        $canonical = $canonicalOverride !== ''
            ? ($this->seo->absoluteUrl($canonicalOverride) ?: url($canonicalOverride))
            : $request->url();

        return SeoData::from([
            'site_name' => $siteName,
            'title' => $resolvedTitle !== '' ? $resolvedTitle : null,
            'description' => $description !== '' ? $description : $defaultDescription,
            'og_image' => $ogImage,
            'og_title' => $ogTitle !== '' ? $ogTitle : null,
            'og_description' => $ogDescription !== '' ? $ogDescription : null,
            'og_type' => $ogType,
            'twitter_card' => 'summary_large_image',
            'article_published_time' => $articlePublished,
            'article_modified_time' => $articleModified,
            'canonical' => $canonical,
            // Sitewide indexable flag must gate this the same way it gates
            // PreventSearchIndexing's X-Robots-Tag header — otherwise a
            // staging build (SEO_INDEXABLE=false) sends the noindex header
            // but still renders <meta name="robots" content="index,follow">
            // client-side. See feedback.md §43.
            'noindex' => ! config('template.indexable') || $siteNoindex || (bool) ($meta['noindex'] ?? false),
            'json_ld' => $jsonLd !== '' ? $jsonLd : null,
        ])->toArray();
    }
}
