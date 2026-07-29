<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ModuleNavEntry extends Data
{
    public function __construct(
        public string $module,
        public string $label,
        public ?string $route,
        public ?string $href,
        public string $icon,
        public ?string $permission,
        // Sidebar section this entry renders under — see ModuleManager::navFor()
        // and AdminLayout.vue's fixed section order.
        public string $group,
    ) {}
}
