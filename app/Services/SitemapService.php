<?php

namespace App\Services;

use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Post;
use App\Modules\Core\ModuleManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RankMath-style sitemap index + per-type children.
 *
 * /sitemap.xml          → sitemapindex (cached as sitemap.index)
 * /sitemap-pages.xml    → urlset
 * /sitemap-posts.xml
 * /sitemap-categories.xml
 * /sitemap-careers.xml
 * /sitemap-case-studies.xml
 *
 * Module enablement via ModuleManager (not bare feature flags). Empty
 * categories (no published posts in self+descendants) are omitted.
 * Bust via forgetAll() from ClearsResponseCache / JsonDataService.
 */
class SitemapService
{
    public const INDEX_KEY = 'sitemap.index';

    public const META_KEY = 'sitemap.meta';

    /** @deprecated Kept so old Cache::forget('sitemap.xml') call sites still clear something. */
    public const LEGACY_KEY = 'sitemap.xml';

    public const TTL = 86400;

    /** @var array<string, string> type → cache key */
    public const CHILD_KEYS = [
        'pages' => 'sitemap.pages',
        'posts' => 'sitemap.posts',
        'categories' => 'sitemap.categories',
        'careers' => 'sitemap.careers',
        'case-studies' => 'sitemap.case-studies',
    ];

    /** @var array<string, string> type → public path */
    public const CHILD_PATHS = [
        'pages' => '/sitemap-pages.xml',
        'posts' => '/sitemap-posts.xml',
        'categories' => '/sitemap-categories.xml',
        'careers' => '/sitemap-careers.xml',
        'case-studies' => '/sitemap-case-studies.xml',
    ];

    public function __construct(
        private JsonDataService $jsonData,
        private ModuleManager $modules,
    ) {}

    /** Sitemap index XML. */
    public function xml(): string
    {
        return Cache::remember(self::INDEX_KEY, self::TTL, function () {
            $entries = [];
            $totalUrls = 0;
            $latest = null;

            foreach (array_keys(self::CHILD_KEYS) as $type) {
                if (! $this->typeEnabled($type)) {
                    continue;
                }

                $urls = $this->urlsFor($type);
                if ($urls === []) {
                    continue;
                }

                $childXml = $this->renderUrlset($urls);
                Cache::put(self::CHILD_KEYS[$type], $childXml, self::TTL);

                $lastmod = $this->maxLastmod($urls);
                $entries[] = [
                    'loc' => url(self::CHILD_PATHS[$type]),
                    'lastmod' => $lastmod,
                ];
                $totalUrls += count($urls);
                if ($lastmod && ($latest === null || $lastmod > $latest)) {
                    $latest = $lastmod;
                }
            }

            $meta = [
                'generated_at' => now()->toIso8601String(),
                'url_count' => $totalUrls,
                'child_count' => count($entries),
                'lastmod' => $latest,
            ];
            Cache::put(self::META_KEY, $meta, self::TTL);

            return $this->renderIndex($entries);
        });
    }

    /** Child sitemap XML for a type (pages, posts, …). */
    public function childXml(string $type): ?string
    {
        if (! isset(self::CHILD_KEYS[$type]) || ! $this->typeEnabled($type)) {
            return null;
        }

        return Cache::remember(self::CHILD_KEYS[$type], self::TTL, function () use ($type) {
            // Ensure index/meta stay consistent when a child is requested first.
            $urls = $this->urlsFor($type);
            if ($urls === []) {
                return null;
            }

            return $this->renderUrlset($urls);
        });
    }

    /**
     * Forget every sitemap cache key, rebuild index + children, return meta.
     *
     * @return array{generated_at: string, url_count: int, child_count: int, lastmod: ?string}
     */
    public function regenerate(): array
    {
        $this->forgetAll();
        $this->xml();

        return $this->meta();
    }

    /**
     * @return array{generated_at: ?string, url_count: int, child_count: int, lastmod: ?string}
     */
    public function meta(): array
    {
        $cached = Cache::get(self::META_KEY);
        if (is_array($cached)) {
            return [
                'generated_at' => $cached['generated_at'] ?? null,
                'url_count' => (int) ($cached['url_count'] ?? 0),
                'child_count' => (int) ($cached['child_count'] ?? 0),
                'lastmod' => $cached['lastmod'] ?? null,
            ];
        }

        return [
            'generated_at' => null,
            'url_count' => 0,
            'child_count' => 0,
            'lastmod' => null,
        ];
    }

    public function forgetAll(): void
    {
        self::forgetStatic();
    }

    /** Static bust for traits / call sites without resolving the container. */
    public static function forgetStatic(): void
    {
        Cache::forget(self::INDEX_KEY);
        Cache::forget(self::META_KEY);
        Cache::forget(self::LEGACY_KEY);
        foreach (self::CHILD_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function typeEnabled(string $type): bool
    {
        return match ($type) {
            'pages' => true,
            'posts', 'categories' => $this->modules->enabled('blog') && Schema::hasTable('posts'),
            'careers' => $this->modules->enabled('careers') && Schema::hasTable('careers'),
            'case-studies' => $this->modules->enabled('case_studies') && Schema::hasTable('case_studies'),
            default => false,
        };
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, images: list<string>}>
     */
    private function urlsFor(string $type): array
    {
        return match ($type) {
            'pages' => $this->pageUrls(),
            'posts' => $this->postUrls(),
            'categories' => $this->categoryUrls(),
            'careers' => $this->careerUrls(),
            'case-studies' => $this->caseStudyUrls(),
            default => [],
        };
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, images: list<string>}>
     */
    private function pageUrls(): array
    {
        $urls = [];
        foreach ($this->jsonData->list('pages') as $slug) {
            $page = $this->jsonData->get("pages/{$slug}");
            if (($page['status'] ?? '') !== 'published') {
                continue;
            }
            if (! empty($page['seo']['noindex'])) {
                continue;
            }

            $path = $slug === 'home' ? '/' : "/{$slug}";
            $file = base_path("data/pages/{$slug}.json");
            $updated = is_file($file) ? date('c', filemtime($file)) : null;
            $images = [];
            $og = $page['featured_image'] ?? $page['seo']['og_image'] ?? '';
            if (is_string($og) && $og !== '') {
                $images[] = $this->absoluteImage($og);
            }
            $urls[] = $this->entry($path, $updated, $images);
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, images: list<string>}>
     */
    private function postUrls(): array
    {
        if (! Schema::hasTable('posts')) {
            return [];
        }

        $urls = [];
        $newest = Post::published()->max('updated_at');
        $urls[] = $this->entry('/blog', $newest ? date('c', strtotime((string) $newest)) : null);

        Post::published()
            ->where(fn ($q) => $q->where('noindex', false)->orWhereNull('noindex'))
            ->get(['slug', 'updated_at', 'featured_image'])
            ->each(function (Post $p) use (&$urls) {
                $images = $p->featured_image ? [$this->absoluteImage($p->featured_image)] : [];
                $urls[] = $this->entry("/blog/{$p->slug}", $p->updated_at?->toAtomString(), $images);
            });

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, images: list<string>}>
     */
    private function categoryUrls(): array
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('category_post')) {
            return [];
        }

        $categories = Category::query()->get(['id', 'slug', 'parent_id', 'updated_at']);
        if ($categories->isEmpty()) {
            return [];
        }

        $directCounts = DB::table('category_post')
            ->join('posts', 'posts.id', '=', 'category_post.post_id')
            ->where('posts.status', 'published')
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->where(fn ($q) => $q->where('posts.noindex', false)->orWhereNull('posts.noindex'))
            ->groupBy('category_post.category_id')
            ->pluck(DB::raw('count(*) as aggregate'), 'category_post.category_id');

        $childrenOf = $categories->groupBy(fn (Category $c) => $c->parent_id ?? 0);

        $descendantMap = [];
        foreach ($categories as $cat) {
            $descendantMap[$cat->id] = $this->collectDescendantIds($cat->id, $childrenOf);
        }

        $urls = [];
        foreach ($categories as $cat) {
            $ids = [$cat->id, ...($descendantMap[$cat->id] ?? [])];
            $count = 0;
            foreach ($ids as $id) {
                $count += (int) ($directCounts[$id] ?? 0);
            }
            if ($count === 0) {
                continue;
            }
            $urls[] = $this->entry(
                "/blog/category/{$cat->slug}",
                $cat->updated_at?->toAtomString(),
            );
        }

        return $urls;
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, Category>>  $childrenOf
     * @return list<int>
     */
    private function collectDescendantIds(int $id, $childrenOf): array
    {
        $ids = [];
        $frontier = [$id];
        while ($frontier !== []) {
            $next = [];
            foreach ($frontier as $fid) {
                foreach ($childrenOf->get($fid, collect()) as $child) {
                    $ids[] = $child->id;
                    $next[] = $child->id;
                }
            }
            $frontier = $next;
        }

        return $ids;
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, images: list<string>}>
     */
    private function careerUrls(): array
    {
        if (! Schema::hasTable('careers')) {
            return [];
        }

        $urls = [];
        $newest = Career::active()->max('updated_at');
        $urls[] = $this->entry('/careers', $newest ? date('c', strtotime((string) $newest)) : null);

        Career::active()->get(['slug', 'updated_at'])
            ->each(function (Career $c) use (&$urls) {
                $urls[] = $this->entry("/careers/{$c->slug}", $c->updated_at?->toAtomString());
            });

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, images: list<string>}>
     */
    private function caseStudyUrls(): array
    {
        if (! Schema::hasTable('case_studies')) {
            return [];
        }

        $urls = [];
        $newest = CaseStudy::active()->max('updated_at');
        $urls[] = $this->entry('/case-studies', $newest ? date('c', strtotime((string) $newest)) : null);

        CaseStudy::active()->get(['slug', 'updated_at', 'featured_image'])
            ->each(function (CaseStudy $c) use (&$urls) {
                $images = $c->featured_image ? [$this->absoluteImage($c->featured_image)] : [];
                $urls[] = $this->entry("/case-studies/{$c->slug}", $c->updated_at?->toAtomString(), $images);
            });

        return $urls;
    }

    /**
     * @param  list<string>  $images
     * @return array{loc: string, lastmod: ?string, images: list<string>}
     */
    private function entry(string $path, ?string $lastmod = null, array $images = []): array
    {
        return [
            'loc' => url($path),
            'lastmod' => $lastmod,
            'images' => array_values(array_filter($images)),
        ];
    }

    /**
     * @param  list<array{loc: string, lastmod: ?string, images: list<string>}>  $urls
     */
    private function maxLastmod(array $urls): ?string
    {
        $max = null;
        foreach ($urls as $url) {
            if ($url['lastmod'] && ($max === null || $url['lastmod'] > $max)) {
                $max = $url['lastmod'];
            }
        }

        return $max;
    }

    /**
     * @param  list<array{loc: string, lastmod: ?string}>  $entries
     */
    private function renderIndex(array $entries): string
    {
        $xsl = e(url('/sitemap.xsl'));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="'.$xsl.'"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= '  <sitemap><loc>'.e($entry['loc']).'</loc>';
            if (! empty($entry['lastmod'])) {
                $xml .= '<lastmod>'.$entry['lastmod'].'</lastmod>';
            }
            $xml .= "</sitemap>\n";
        }

        return $xml.'</sitemapindex>';
    }

    /**
     * @param  list<array{loc: string, lastmod: ?string, images: list<string>}>  $urls
     */
    private function renderUrlset(array $urls): string
    {
        $hasImages = collect($urls)->contains(fn ($u) => $u['images'] !== []);
        $xsl = e(url('/sitemap.xsl'));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="'.$xsl.'"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ($hasImages) {
            $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }
        $xml .= ">\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }
            foreach ($url['images'] as $image) {
                $xml .= '<image:image><image:loc>'.e($image).'</image:loc></image:image>';
            }
            $xml .= "</url>\n";
        }

        return $xml.'</urlset>';
    }

    private function absoluteImage(string $path): string
    {
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        return url($path);
    }
}
