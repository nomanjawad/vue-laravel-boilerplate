<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ModulesSharedData extends Data
{
    /**
     * @param  array<int, ModuleNavEntry>  $nav
     * @param  array<int, string>  $enabled
     */
    public function __construct(
        public array $nav,
        public array $enabled,
    ) {}
}
