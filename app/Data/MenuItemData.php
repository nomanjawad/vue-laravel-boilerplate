<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MenuItemData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public int $sort_order,
    ) {}
}
