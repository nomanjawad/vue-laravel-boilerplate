<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SettingGroupData extends Data
{
    /**
     * @param  array<int, SettingFieldData>  $fields
     */
    public function __construct(
        /** DB `site_settings.group` value */
        public string $group,
        public string $label,
        public array $fields,
    ) {}
}
