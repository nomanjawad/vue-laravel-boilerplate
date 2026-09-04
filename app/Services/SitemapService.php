<?php

namespace App\Services;

use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Builds /sitemap.xml from published JSON pages + content modules, respecting
 * feature flags, noindex, and image entries. Cached as a string; the cache key
 * is forgotten by ClearsResponseCache / JsonDataService whenever content changes.
 */
class SitemapService
{
    public const CACHE_KEY = 'sitemap.xml';

    public function __construct(private JsonDataService $jsonData) {}

    public function xml(): string
    {
        // IMPORTANT: only cache plain scalars/arrays here — Collections/models do not
        // round-trip reliably through the file cache driver used on shared hosting.
        return Cache::remember(self::CACHE_KEY, 86400, fn () => $this->build());
    }

    private function build(): string
    {
        /** @var list<array{loc: string, lastmod: ?string, images: list<string>}> $urls */
        $urls = [];

        $add = function (string $path, ?string $updated = null, array $images = []) use (&$urls): void {
            $urls[] = [
                'loc' => url($path),
                'lastmod' => $updated,
                'images' => array_values(array_filter($images)),
            ];
        };

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
            $og = $page['seo']['og_image'] ?? '';
            if (is_string($og) && $og !== '') {
                $images[] = $this->absoluteImage($og);
            }
            $add($path, $updated, $images);
        }

        if (config('template.features.blog') && Schema::hasTable('posts')) {
            $add('/blog');
            if (Schema::hasTable('categories')) {
                Category::query()->get(['slug', 'updated_at'])
                    ->each(fn ($c) => $add("/blog/category/{$c->slug}", $c->updated_at?->toAtomString()));
            }
            Post::published()
                ->where(fn ($q) => $q->where('noindex', false)->orWhereNull('noindex'))
                ->get(['slug', 'updated_at', 'featured_image', 'og_image'])
                ->each(function ($p) use ($add) {
                    $images = [];
                    foreach ([$p->og_image, $p->featured_image] as $img) {
                        if ($img) {
                            $images[] = $this->absoluteImage($img);
                        }
                    }
                    $add("/blog/{$p->slug}", $p->updated_at?->toAtomString(), array_unique($images));
                });
        }

        if (config('template.features.careers') && Schema::hasTable('careers')) {
            $add('/careers');
            Career::active()->get(['slug', 'updated_at'])
                ->each(fn ($c) => $add("/careers/{$c->slug}", $c->updated_at?->toAtomString()));
        }

        if (config('template.features.case_studies') && Schema::hasTable('case_studies')) {
            $add('/case-studies');
            CaseStudy::active()->get(['slug', 'updated_at', 'featured_image'])
                ->each(function ($c) use ($add) {
                    $images = $c->featured_image ? [$this->absoluteImage($c->featured_image)] : [];
                    $add("/case-studies/{$c->slug}", $c->updated_at?->toAtomString(), $images);
                });
        }

        $hasImages = collect($urls)->contains(fn ($u) => $u['images'] !== []);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
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
