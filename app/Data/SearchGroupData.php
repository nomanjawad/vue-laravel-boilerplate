<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SearchGroupData extends Data
{
    /**
     * @param  array<int, SearchResultData>  $results
     */
    public function __construct(
        public string $module,
        public string $label,
        public array $results,
    ) {}
}
