<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SeoData extends Data
{
    public function __construct(
        public string $site_name,
        /** Fully resolved document title (template already applied when needed). */
        public ?string $title,
        public string $description,
        public ?string $og_image,
        /** Absolute self-referencing canonical (or admin override). */
        public string $canonical,
        public bool $noindex = false,
        // Raw JSON-LD text as entered by the admin (Pages panel), not a
        // decoded structure — PublicLayout writes it verbatim into a <script
        // type="application/ld+json"> placed right after <body> for that page.
        public ?string $json_ld = null,
        /** og:title override; falls back to $title when null/empty. */
        public ?string $og_title = null,
        /** og:description override; falls back to $description when null/empty. */
        public ?string $og_description = null,
        public string $og_type = 'website',
        public string $twitter_card = 'summary_large_image',
        public ?string $article_published_time = null,
        public ?string $article_modified_time = null,
    ) {}
}
