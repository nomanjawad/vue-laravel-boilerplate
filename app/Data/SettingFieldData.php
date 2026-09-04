<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SettingFieldData extends Data
{
    /**
     * @param  list<array{value: string, label: string}>|null  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        /** text | textarea | image | email | tel | url | toggle | password | color | select */
        public string $input = 'text',
        public ?string $placeholder = null,
        public ?string $help = null,
        public bool $is_secret = false,
        public ?string $value = null,
        /** Select options (font, radius, …). */
        public ?array $options = null,
    ) {}
}
