<?php

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\LaravelTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;

/**
 * Scans app/Data + every module's Data/ folder for laravel-data DTOs (and any
 * class marked with #[TypeScript]) and emits resources/js/types/types.d.ts.
 * Wired into `composer ide` so a renamed DTO field surfaces in the IDE
 * before runtime.
 */
class TypeScriptTransformerServiceProvider extends TypeScriptTransformerApplicationServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(
                app_path('Data'),
                app_path('Modules'),
            )
            ->extension(new LaravelTypeScriptTransformerExtension())
            ->extension(new LaravelDataTypeScriptTransformerExtension())
            ->outputDirectory(resource_path('js/types'));
    }
}
