<?php

namespace App\Modules\Core\Contracts;

/**
 * Contract every module ServiceProvider must satisfy so the registry can
 * treat virtual (config-only) and physical (folder-on-disk) modules uniformly.
 */
interface ModuleProvider
{
    public function moduleKey(): string;

    public function moduleManifest(): array;

    public function bootModule(): void;
}
