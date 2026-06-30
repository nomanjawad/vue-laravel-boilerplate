<?php

namespace App\Modules\Core;

use App\Modules\Core\Contracts\ModuleProvider;

/**
 * Lightweight provider that wraps a virtual module (config-only, no folder).
 * Used to back-compat-register the v2 features (blog, shop, careers, ...) as
 * modules in the registry without physically moving any code yet.
 */
class VirtualModuleProvider implements ModuleProvider
{
    public function __construct(public string $key, public array $manifest) {}

    public function moduleKey(): string
    {
        return $this->key;
    }

    public function moduleManifest(): array
    {
        return $this->manifest;
    }

    public function bootModule(): void
    {
        // Route loading for virtual modules happens in
        // App\Providers\ModulesServiceProvider — they share the existing
        // routes/ files for backwards compatibility.
    }
}
