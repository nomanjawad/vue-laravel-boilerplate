<?php

namespace App\Providers;

use App\Modules\Core\AbstractModuleServiceProvider;
use App\Modules\Core\Middleware\EnsureModuleEnabled;
use App\Modules\Core\ModuleManager;
use App\Modules\Core\PermissionSyncer;
use App\Modules\Core\VirtualModuleProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * The orchestrator. Boots every registered module (virtual + physical),
 * wrapping each in try/catch so a single broken module can never 500 the
 * whole panel.
 *
 * Discovery order:
 *   1. config/modules.php          → virtual modules (v2 back-compat)
 *   2. app/Modules/*­/Module*ServiceProvider.php → physical modules
 *
 * Both register their manifests with ModuleManager. Then for each enabled
 * module we call bootModule() inside rescue() — failures get marked
 * unhealthy in the registry, the panel keeps booting.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(PermissionSyncer::class);

        $manager = $this->app->make(ModuleManager::class);

        // 1. Virtual modules from config (existing v2 features, no folder yet).
        foreach (config('modules', []) as $key => $manifest) {
            $manifest = array_merge(['key' => $key], $manifest);
            $manager->registerManifest($key, $manifest);
            $manager->registerProvider($key, new VirtualModuleProvider($key, $manifest));
        }

        // 2. Physical modules at app/Modules/{X}/Module*ServiceProvider.php.
        // Discovery is glob-based so adding a module is a single drop-in.
        foreach (glob(app_path('Modules/*/Module*ServiceProvider.php')) ?: [] as $file) {
            $relative = str_replace([app_path(), '.php', '/'], ['App', '', '\\'], $file);
            try {
                /** @var AbstractModuleServiceProvider $provider */
                $provider = new $relative($this->app);
                $manifest = $provider->moduleManifest();
                $manager->registerManifest($provider->moduleKey(), $manifest);
                $manager->registerProvider($provider->moduleKey(), $provider);
                // Register-phase bindings (only when enabled — keeps disabled
                // modules' code dormant).
                if ($manager->enabled($provider->moduleKey())) {
                    rescue(
                        fn () => $provider->register(),
                        report: fn (Throwable $e) => $manager->markUnhealthy($provider->moduleKey(), $e),
                    );
                }
            } catch (Throwable $e) {
                // Do NOT re-instantiate — if the constructor threw, calling it
                // again here re-throws inside catch and escapes fault isolation.
                // Fall back to the folder name.
                $key = strtolower(basename(dirname($file)));
                report($e);
                rescue(fn () => $manager->markUnhealthy($key, $e), report: false);
            }
        }
    }

    public function boot(Router $router, ModuleManager $manager): void
    {
        // Make the module-gate middleware available as `module:{key}`.
        $router->aliasMiddleware('module', EnsureModuleEnabled::class);

        // Boot each enabled module. The try/catch is the fault-isolation
        // guarantee — a thrown bootModule() will not crash the rest.
        foreach ($manager->manifests() as $key => $manifest) {
            if (! $manager->enabled($key)) {
                continue;
            }

            $provider = $manager->provider($key);
            if (! $provider) {
                continue;
            }

            rescue(
                fn () => $provider->bootModule(),
                report: fn (Throwable $e) => $manager->markUnhealthy($key, $e),
            );
        }
    }
}
