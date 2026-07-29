<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SeoData extends Data
{
    public function __construct(
        public string $site_name,
        public ?string $title,
        public string $description,
        public ?string $og_image,
        public string $canonical,
        public bool $noindex = false,
        // Raw JSON-LD text as entered by the admin (Page Content panel), not a
        // decoded structure — PublicLayout writes it verbatim into a <script
        // type="application/ld+json"> placed right after <body> for that page.
        public ?string $json_ld = null,
    ) {}
}
