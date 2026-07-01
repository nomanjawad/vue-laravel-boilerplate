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
    ) {}
}
