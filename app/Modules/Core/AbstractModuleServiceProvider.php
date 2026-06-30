<?php

namespace App\Modules\Core;

use App\Modules\Core\Contracts\ModuleProvider;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Base class for physical modules under app/Modules/{X}/.
 *
 * Subclasses set `$key` + `$name` + `$dependencies` + `$manifestPath`,
 * implement `bootModule()` to load their routes/migrations/views.
 *
 * The orchestration provider (App\Providers\ModulesServiceProvider) iterates
 * these and wraps the boot in try/catch so a single broken module can never
 * crash the whole panel.
 */
abstract class AbstractModuleServiceProvider extends ServiceProvider implements ModuleProvider
{
    public string $key;

    public string $name;

    public string $version = '1.0.0';

    /** @var array<int, string> */
    public array $dependencies = [];

    /** Path to the module's `module.php` manifest file. */
    public string $manifestPath;

    /**
     * Override to do extra work when the module is enabled.
     */
    public function bootModule(): void
    {
        $base = $this->modulePath();

        $routes = $base.'/Routes';
        if (is_dir($routes)) {
            foreach (glob($routes.'/*.php') as $file) {
                $this->loadRoutesFrom($file);
            }
        }

        $migrations = $base.'/Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $views = $base.'/Views';
        if (is_dir($views)) {
            $this->loadViewsFrom($views, $this->key);
        }

        $factories = $base.'/Database/Factories';
        if (is_dir($factories)) {
            // Factories auto-discovered by Laravel as long as the model points
            // at the right path; no explicit registration needed.
        }
    }

    public function moduleKey(): string
    {
        return $this->key;
    }

    public function moduleManifest(): array
    {
        if (file_exists($this->manifestPath)) {
            $manifest = require $this->manifestPath;
            $manifest['migrations_path'] = $this->modulePath().'/Database/Migrations';

            return $manifest;
        }

        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'dependencies' => $this->dependencies,
            'migrations_path' => $this->modulePath().'/Database/Migrations',
        ];
    }

    protected function modulePath(): string
    {
        return dirname((new \ReflectionClass($this))->getFileName());
    }
}
