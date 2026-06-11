<?php

namespace App\Services;

use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Builds /sitemap.xml from static routes + published content, respecting
 * feature flags. Cached as a string; the cache key is forgotten by
 * ClearsResponseCache whenever any content model is saved or deleted.
 */
class SitemapService
{
    public const CACHE_KEY = 'sitemap.xml';

    public function xml(): string
    {
        // IMPORTANT: only cache plain scalars/arrays here — Collections/models do not
        // round-trip reliably through the file cache driver used on shared hosting.
        return Cache::remember(self::CACHE_KEY, 86400, fn () => $this->build());
    }

    private function build(): string
    {
        $urls = [];

        $add = function (string $path, ?string $updated = null) use (&$urls): void {
            $urls[] = ['loc' => url($path), 'lastmod' => $updated];
        };

        $add('/');
        $add('/about');

        if (config('template.features.contact_form')) {
            $add('/contact');
        }

        if (Schema::hasTable('pages')) {
            Page::published()->get(['slug', 'updated_at'])
                ->each(fn ($p) => $add("/page/{$p->slug}", $p->updated_at?->toAtomString()));
        }

        if (config('template.features.blog') && Schema::hasTable('posts')) {
            $add('/blog');
            Post::published()->get(['slug', 'updated_at'])
                ->each(fn ($p) => $add("/blog/{$p->slug}", $p->updated_at?->toAtomString()));
        }

        if (config('template.features.shop') && Schema::hasTable('products')) {
            $add('/shop');
            Product::active()->get(['slug', 'updated_at'])
                ->each(fn ($p) => $add("/shop/{$p->slug}", $p->updated_at?->toAtomString()));
        }

        if (config('template.features.careers') && Schema::hasTable('careers')) {
            $add('/careers');
            Career::active()->get(['slug', 'updated_at'])
                ->each(fn ($c) => $add("/careers/{$c->slug}", $c->updated_at?->toAtomString()));
        }

        if (config('template.features.case_studies') && Schema::hasTable('case_studies')) {
            $add('/case-studies');
            CaseStudy::active()->get(['slug', 'updated_at'])
                ->each(fn ($c) => $add("/case-studies/{$c->slug}", $c->updated_at?->toAtomString()));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }
            $xml .= "</url>\n";
        }

        return $xml.'</urlset>';
    }
}
