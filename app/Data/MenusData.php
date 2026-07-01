<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MenusData extends Data
{
    /**
     * @param  array<int, MenuItemData>  $header
     * @param  array<int, MenuItemData>  $footer
     */
    public function __construct(
        public array $header,
        public array $footer,
    ) {}
}
