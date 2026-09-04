<?php

namespace App\Services;

use App\Models\Career;
use App\Models\Post;
use App\Models\Setting;

class SeoService
{
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

        $logo = Setting::get('site_logo');
        if ($logo && ! preg_match('#^https?://#', $logo)) {
            $logo = url($logo);
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::get('site_name') ?: config('app.name'),
            'url' => url('/'),
            'logo' => $logo ?: null,
            'email' => Setting::get('contact_email'),
            'telephone' => Setting::get('contact_phone'),
            'sameAs' => $sameAs ?: null,
        ]);
    }

    /**
     * LocalBusiness schema from SEO settings — emitted site-wide when enabled
     * and at least a type + name are present. Phone/address fall back to
     * contact settings when the LocalBusiness-specific fields are blank.
     */
    public function localBusiness(): ?array
    {
        if (Setting::get('local_business_enabled') !== '1') {
            return null;
        }

        $type = Setting::get('local_business_type') ?: 'LocalBusiness';
        $name = Setting::get('site_name') ?: config('app.name');
        if (! $name) {
            return null;
        }

        $street = Setting::get('local_business_street') ?: null;
        $city = Setting::get('local_business_city') ?: null;
        $region = Setting::get('local_business_region') ?: null;
        $postal = Setting::get('local_business_postal') ?: null;
        $country = Setting::get('local_business_country') ?: null;

        // Fall back to the single-line contact address when structured fields
        // are empty (common for small service businesses).
        $address = null;
        if ($street || $city || $region || $postal || $country) {
            $address = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $street,
                'addressLocality' => $city,
                'addressRegion' => $region,
                'postalCode' => $postal,
                'addressCountry' => $country,
            ]);
        } elseif ($fallback = Setting::get('address')) {
            $address = [
                '@type' => 'PostalAddress',
                'streetAddress' => $fallback,
            ];
        }

        $lat = Setting::get('local_business_lat');
        $lng = Setting::get('local_business_lng');
        $geo = ($lat !== null && $lat !== '' && $lng !== null && $lng !== '')
            ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ]
            : null;

        $hoursRaw = trim((string) Setting::get('local_business_opening_hours'));
        $hours = $hoursRaw !== ''
            ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $hoursRaw) ?: [])))
            : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'url' => url('/'),
            'telephone' => Setting::get('contact_phone') ?: null,
            'email' => Setting::get('contact_email') ?: null,
            'address' => $address,
            'geo' => $geo,
            'openingHours' => $hours ?: null,
        ]);
    }

    /** BlogPosting schema for a published (or previewed) post. */
    public function blogPosting(Post $post): array
    {
        $image = $post->og_image ?: $post->featured_image;
        if ($image && ! preg_match('#^https?://#', $image)) {
            $image = url($image);
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->meta_title ?: $post->title,
            'description' => $post->meta_description ?: $post->excerpt,
            'image' => $image ?: null,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'author' => $post->user ? ['@type' => 'Person', 'name' => $post->user->name] : null,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url("/blog/{$post->slug}"),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => Setting::get('site_name') ?: config('app.name'),
            ],
        ]);
    }

    /** @deprecated Use blogPosting() — kept as an alias for callers. */
    public function article(Post $post): array
    {
        return $this->blogPosting($post);
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
                'item' => str_starts_with($crumb['url'], 'http') ? $crumb['url'] : url($crumb['url']),
            ])->all(),
        ];
    }

    /**
     * FAQPage schema from a list of Q&A pairs (FAQ widget / page-wise FAQs).
     *
     * @param  array<int, array{question?: string, answer?: string}>  $faqs
     */
    public function faqPage(array $faqs): ?array
    {
        $entities = [];
        foreach ($faqs as $faq) {
            $q = trim((string) ($faq['question'] ?? ''));
            $a = trim(strip_tags((string) ($faq['answer'] ?? '')));
            if ($q === '' || $a === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }

        if ($entities === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * Apply the site title template. Explicit meta titles are returned as-is;
     * otherwise `%title%` / `%site_name%` tokens are replaced.
     */
    public function applyTitleTemplate(?string $metaTitle, string $contentTitle, string $siteName, ?string $template = null): string
    {
        $metaTitle = trim((string) $metaTitle);
        if ($metaTitle !== '') {
            return $metaTitle;
        }

        $template = $template ?? Setting::get('seo_title_template') ?: '%title% — %site_name%';
        $title = trim($contentTitle) !== '' ? $contentTitle : $siteName;

        return str_replace(
            ['%title%', '%site_name%'],
            [$title, $siteName],
            $template,
        );
    }

    /** Promote a root-relative media URL to absolute for crawlers. */
    public function absoluteUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        return url($path);
    }
}
