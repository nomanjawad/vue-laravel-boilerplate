<?php

namespace App\Services;

use App\Models\Career;
use App\Models\PageMeta;
use App\Models\Post;
use App\Models\Setting;

class SeoService
{
    public function getMetaForRoute(string $routeName): array
    {
        $meta = PageMeta::where('route_name', $routeName)->first();

        if (! $meta) {
            return [];
        }

        return [
            'title' => $meta->meta_title,
            'description' => $meta->meta_description,
            'og_image' => $meta->og_image,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | JSON-LD structured data
    |--------------------------------------------------------------------------
    | Pass these from controllers as a `jsonLd` Inertia prop (array of schema
    | objects); PublicLayout renders them as <script type="application/ld+json">.
    */

    /** Organization schema from site settings — shared on every public page. */
    public function organization(): array
    {
        $sameAs = array_values(array_filter([
            Setting::get('facebook'),
            Setting::get('twitter'),
            Setting::get('instagram'),
            Setting::get('linkedin'),
            Setting::get('youtube'),
        ]));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::get('site_name') ?: config('app.name'),
            'url' => url('/'),
            'email' => Setting::get('contact_email'),
            'telephone' => Setting::get('contact_phone'),
            'sameAs' => $sameAs ?: null,
        ]);
    }

    public function article(Post $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $post->featured_image ? url($post->featured_image) : null,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'author' => $post->user ? ['@type' => 'Person', 'name' => $post->user->name] : null,
            'mainEntityOfPage' => url("/blog/{$post->slug}"),
        ]);
    }

    /** JobPosting schema — Google Jobs picks these up. */
    public function jobPosting(Career $career): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $career->title,
            'description' => $career->description,
            'datePosted' => $career->created_at?->toAtomString(),
            'employmentType' => str_replace('-', '_', strtoupper($career->type ?? 'full-time')),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => Setting::get('site_name') ?: config('app.name'),
                'sameAs' => url('/'),
            ],
            'jobLocation' => $career->location ? [
                '@type' => 'Place',
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $career->location],
            ] : null,
        ]);
    }

    /** @param array<int, array{name: string, url: string}> $crumbs */
    public function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => url($crumb['url']),
            ])->all(),
        ];
    }
}
