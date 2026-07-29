<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FlashData extends Data
{
    public function __construct(
        public string|Optional|null $success = null,
        public string|Optional|null $error = null,
        public string|Optional|null $info = null,
        public string|Optional|null $warning = null,
        // Set by MediaController::store so AppMediaPicker can update its
        // v-model from the redirect response without an extra fetch.
        public MediaData|Optional|null $media = null,
    ) {}
}
