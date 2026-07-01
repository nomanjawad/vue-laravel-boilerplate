<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SearchResultData extends Data
{
    public function __construct(
        public int|string $id,
        public string $title,
        public ?string $subtitle,
        public string $href,
    ) {}
}
