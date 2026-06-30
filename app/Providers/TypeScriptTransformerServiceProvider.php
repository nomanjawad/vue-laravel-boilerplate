<?php

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTransformedProvider;
use Spatie\LaravelTypeScriptTransformer\Transformers\LaravelAttributedClassTransformer;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\TransformedProviders\ReflectionTransformedProvider;
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
            ->transformDirectories(
                app_path('Data'),
                app_path('Modules'),
            )
            ->transformer(LaravelAttributedClassTransformer::class)
            ->provider(ReflectionTransformedProvider::class)
            ->provider(LaravelDataTransformedProvider::class)
            ->outputDirectory(resource_path('js/types'));
    }
}
