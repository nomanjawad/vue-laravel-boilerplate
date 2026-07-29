<?php

namespace App\Modules\Core;

use App\Modules\Core\Exceptions\ModuleDependencyException;
use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\Exceptions\ModuleNotFoundException;
use App\Modules\Core\Models\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Central registry for modules.
 *
 * Reads the `modules` table (cached as a plain array — file-cache safe per v2
 * conventions). Falls back to config('template.features.{key}') when the DB or
 * table isn't reachable yet (fresh install, migrating during install) so the
 * panel can boot in any state.
 */
class ModuleManager
{
    private const CACHE_KEY = 'modules.registry';

    /** @var array<string, array> Manifests keyed by module key. */
    protected array $manifests = [];

    /** @var array<string, \App\Modules\Core\Contracts\ModuleProvider> Boot providers keyed by key. */
    protected array $providers = [];

    public function registerManifest(string $key, array $manifest): void
    {
        $this->manifests[$key] = array_merge([
            'key' => $key,
            'name' => ucfirst($key),
            'description' => '',
            'version' => '1.0.0',
            'dependencies' => [],
            'permissions' => [],
            'nav' => [],
            // Which sidebar section this module's nav entries render under.
            // See navFor() and AdminLayout.vue's fixed section order.
            'nav_group' => 'content',
            'core' => false,
            'searchable' => [],
            'feature_flag_fallback' => $key,
        ], $manifest);
    }

    public function registerProvider(string $key, \App\Modules\Core\Contracts\ModuleProvider $provider): void
    {
        $this->providers[$key] = $provider;
    }

    public function provider(string $key): ?\App\Modules\Core\Contracts\ModuleProvider
    {
        return $this->providers[$key] ?? null;
    }

    /** @return array<string, array> */
    public function manifests(): array
    {
        return $this->manifests;
    }

    public function manifest(string $key): array
    {
        if (! isset($this->manifests[$key])) {
            throw new ModuleNotFoundException("Module [{$key}] is not registered.");
        }

        return $this->manifests[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->manifests[$key]);
    }

    /**
     * Is the module enabled? Reads cached registry from DB; on any failure
     * (DB down, table missing, migration mid-flight) falls back to the
     * config('template.features.*') flag so the boot path never throws.
     */
    public function enabled(string $key): bool
    {
        $manifest = $this->manifests[$key] ?? null;

        if (($manifest['core'] ?? false) === true) {
            return true;
        }

        $registry = $this->registry();

        if (isset($registry[$key])) {
            return ! empty($registry[$key]['enabled']) && empty($registry[$key]['unhealthy']);
        }

        return (bool) config(
            'template.features.'.($manifest['feature_flag_fallback'] ?? $key),
            false
        );
    }

    /**
     * Whether the module's manifest `nav` entries should show in the sidebar.
     * Independent of `enabled()` — a module can be enabled but nav-hidden, or
     * (in principle) disabled with nav still visible (which navFor() then
     * suppresses because it also checks `enabled`).
     *
     * Defaults to true when the registry row is missing (fresh install) so
     * new modules appear in the sidebar until an admin explicitly hides them.
     */
    public function navVisible(string $key): bool
    {
        $registry = $this->registry();

        if (! isset($registry[$key])) {
            return true;
        }

        return (bool) ($registry[$key]['nav_visible'] ?? true);
    }

    /**
     * Toggle a module's nav visibility. Core modules ARE allowed here — the
     * whole point of the flag is to hide `menus` / `page_content` / etc. from
     * projects that don't use them, without disabling them.
     */
    public function setNavVisible(string $key, bool $visible): void
    {
        if (! isset($this->manifests[$key])) {
            throw new ModuleNotFoundException("Unknown module: {$key}");
        }
        if (! $this->tablesReady()) {
            return;
        }

        Module::updateOrCreate(
            ['key' => $key],
            ['nav_visible' => $visible],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Was the module flagged unhealthy in the registry?
     *
     * Returns false when the registry table isn't reachable — during a DB
     * outage or fresh install there is no signal, and `enabled()` falls back
     * to the config flag. Callers should treat `false` as "no known problem"
     * rather than a definitive healthy answer.
     */
    public function unhealthy(string $key): bool
    {
        $registry = $this->registry();

        return ! empty($registry[$key]['unhealthy'] ?? false);
    }

    /** @return array<string, array{enabled:bool, unhealthy:bool, last_error:?string, last_error_at:?string, installed_version:?string}> */
    public function registry(): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        // The cache facade may not be bound yet if `enabled()` is called during
        // the service-provider register phase (before Laravel binds the cache
        // manager). Fall back to an empty registry — `enabled()` then falls
        // back to config('template.features.*'), and the panel still boots.
        if (! app()->bound('cache')) {
            return [];
        }

        // Defensive: registry() is called during service-provider boot for
        // every request. A pending migration (missing column), DB outage,
        // or wrong credentials must NOT 500 the whole panel — enabled()
        // falls back to config('template.features.*') when this returns
        // empty, so a degraded but functional site is always available.
        // The exception is `report()`ed so Sentry/log picks it up.
        try {
            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                // Plain array — Cache::remember must round-trip through file driver
                // per v2 conventions (Cache::remember stores arrays, never Collections).
                return Module::query()
                    ->get(['key', 'enabled', 'nav_visible', 'unhealthy', 'last_error', 'last_error_at', 'installed_version'])
                    ->keyBy('key')
                    ->map(fn ($m) => [
                        'enabled' => (bool) $m->enabled,
                        'nav_visible' => (bool) $m->nav_visible,
                        'unhealthy' => (bool) $m->unhealthy,
                        'last_error' => $m->last_error,
                        'last_error_at' => optional($m->last_error_at)->toIso8601String(),
                        'installed_version' => $m->installed_version,
                    ])
                    ->toArray();
            });
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Enable a module: verify dependencies, run its migrations + seeders,
     * sync permissions, clear caches. Idempotent.
     */
    public function enable(string $key): void
    {
        $manifest = $this->manifest($key);

        foreach ($manifest['dependencies'] as $dep) {
            if (! $this->enabled($dep)) {
                throw new ModuleDependencyException(
                    "Module [{$key}] requires [{$dep}], which is disabled. Enable [{$dep}] first."
                );
            }
        }

        $existing = Module::where('key', $key)->first();

        Module::updateOrCreate(
            ['key' => $key],
            [
                'type' => isset($this->providers[$key]) ? 'physical' : 'virtual',
                'enabled' => true,
                'unhealthy' => false,
                'last_error' => null,
                'last_error_at' => null,
                'installed_version' => $manifest['version'] ?? '1.0.0',
                'installed_at' => $existing?->installed_at ?? now(),
                'disabled_at' => null,
            ],
        );

        $this->forgetCache();

        // Migrations run outside a wrapping transaction (Artisan manages its
        // own), so we can't roll back on partial failure. Instead, catch any
        // seeder/permission-sync throw and mark the module unhealthy — the
        // admin then sees a red badge and can Reinstall from /admin/modules
        // rather than silently drifting into a half-installed state.
        try {
            $this->runMigrations($key);

            foreach ($manifest['seeders'] ?? [] as $seederClass) {
                Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
            }

            if (app()->bound(PermissionSyncer::class)) {
                app(PermissionSyncer::class)->sync();
            }
        } catch (Throwable $e) {
            $this->markUnhealthy($key, $e);
            throw $e;
        }

        rescue(fn () => Artisan::call('responsecache:clear'), report: false);
    }

    public function disable(string $key): void
    {
        $manifest = $this->manifest($key);

        if (! empty($manifest['core'])) {
            throw new ModuleException("Module [{$key}] is core and cannot be disabled.");
        }

        // Block disabling a module that other enabled modules depend on.
        foreach ($this->manifests as $otherKey => $other) {
            if ($otherKey === $key) {
                continue;
            }
            if ($this->enabled($otherKey) && in_array($key, $other['dependencies'] ?? [], true)) {
                throw new ModuleDependencyException(
                    "Cannot disable [{$key}]: module [{$otherKey}] depends on it. Disable [{$otherKey}] first."
                );
            }
        }

        Module::updateOrCreate(
            ['key' => $key],
            ['enabled' => false, 'disabled_at' => now()],
        );

        $this->forgetCache();
        rescue(fn () => Artisan::call('responsecache:clear'), report: false);
    }

    public function markUnhealthy(string $key, Throwable $e): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        rescue(function () use ($key, $e) {
            Module::updateOrCreate(
                ['key' => $key],
                [
                    'unhealthy' => true,
                    'last_error' => substr($e->getMessage(), 0, 1000),
                    'last_error_at' => now(),
                ],
            );
            $this->forgetCache();
        }, report: false);

        Log::warning("Module [{$key}] marked unhealthy: ".$e->getMessage());
    }

    public function clearHealth(string $key): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        Module::where('key', $key)->update([
            'unhealthy' => false,
            'last_error' => null,
            'last_error_at' => null,
        ]);
        $this->forgetCache();
    }

    public function reinstall(string $key): void
    {
        $manifest = $this->manifest($key);
        $this->runMigrations($key);
        foreach ($manifest['seeders'] ?? [] as $seederClass) {
            Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
        }
        $this->clearHealth($key);
    }

    /**
     * Roll back the module's migrations and detach its permissions.
     * Destructive — confirmation lives in the UI layer.
     */
    public function uninstall(string $key): void
    {
        $manifest = $this->manifest($key);

        if (! empty($manifest['core'])) {
            throw new ModuleException("Module [{$key}] is core and cannot be uninstalled.");
        }

        // Same dependent-guard as disable(): uninstalling a module still
        // depended on by others would rip out its permissions and migrations
        // while their code still references them.
        foreach ($this->manifests as $otherKey => $other) {
            if ($otherKey === $key) {
                continue;
            }
            if ($this->enabled($otherKey) && in_array($key, $other['dependencies'] ?? [], true)) {
                throw new ModuleDependencyException(
                    "Cannot uninstall [{$key}]: module [{$otherKey}] depends on it. Disable [{$otherKey}] first."
                );
            }
        }

        $path = $manifest['migrations_path'] ?? null;
        if ($path && is_dir($path)) {
            Artisan::call('migrate:rollback', [
                '--path' => str_replace(base_path().'/', '', $path),
                '--force' => true,
            ]);
        }

        if (app()->bound(PermissionSyncer::class)) {
            app(PermissionSyncer::class)->dropFor($manifest);
        }

        Module::where('key', $key)->update([
            'enabled' => false,
            'installed_version' => null,
            'installed_at' => null,
            'disabled_at' => now(),
        ]);
        $this->forgetCache();
    }

    /**
     * @return array<int, array{key:string, name:string, description:string, version:string, installed_version:?string, dependencies:array, enabled:bool, nav_visible:bool, has_nav:bool, unhealthy:bool, last_error:?string, core:bool}>
     */
    public function summary(): array
    {
        $registry = $this->registry();
        $rows = [];
        foreach ($this->manifests as $key => $manifest) {
            $reg = $registry[$key] ?? [];
            $rows[] = [
                'key' => $key,
                'name' => $manifest['name'],
                'description' => $manifest['description'] ?? '',
                'version' => $manifest['version'] ?? '1.0.0',
                'installed_version' => $reg['installed_version'] ?? null,
                'dependencies' => $manifest['dependencies'] ?? [],
                'enabled' => $this->enabled($key),
                'nav_visible' => $this->navVisible($key),
                // has_nav is a UI hint: if the manifest declares no nav entries
                // the "Show in nav" toggle would have nothing to hide, so the
                // /admin/modules page can suppress it. Cheap; keeps UI honest.
                'has_nav' => ! empty($manifest['nav'] ?? []),
                'unhealthy' => $reg['unhealthy'] ?? false,
                'last_error' => $reg['last_error'] ?? null,
                'core' => (bool) ($manifest['core'] ?? false),
            ];
        }

        return $rows;
    }

    /**
     * Sidebar nav flattened from enabled-module manifests + the user's permissions.
     * Returns an ordered array of nav entries the frontend can render directly.
     *
     * @param  array<int, string>  $userPermissions
     * @return array<int, array>
     */
    public function navFor(array $userPermissions, bool $isSuperAdmin = false): array
    {
        $nav = [];
        foreach ($this->manifests as $key => $manifest) {
            if (! $this->enabled($key)) {
                continue;
            }
            // Enabled ≠ nav-visible. A module can be fully active while an
            // admin has hidden its sidebar entries via /admin/modules
            // (feedback.md §11). Also lets core modules — which can't be
            // disabled — be trimmed from the nav without hand-editing config.
            if (! $this->navVisible($key)) {
                continue;
            }
            foreach ($manifest['nav'] ?? [] as $entry) {
                if (! is_array($entry) || empty($entry['label'])) {
                    continue;
                }
                $permission = $entry['permission'] ?? null;
                if ($permission && ! $isSuperAdmin && ! in_array($permission, $userPermissions, true)) {
                    continue;
                }
                // Defensive defaults — manifests that omit icon or route
                // shouldn't crash the layout's template render. `group` comes
                // from the module's manifest (one section per module), not
                // per nav entry — a nav item can still override it explicitly
                // if a module ever needs to split across sections.
                $nav[] = array_merge([
                    'module' => $key,
                    'label' => '',
                    'icon' => 'cube',
                    'route' => null,
                    'href' => null,
                    'permission' => null,
                    'group' => $manifest['nav_group'] ?? 'content',
                ], $entry);
            }
        }

        return $nav;
    }

    protected function runMigrations(string $key): void
    {
        $manifest = $this->manifests[$key] ?? [];
        $path = $manifest['migrations_path'] ?? null;
        if ($path && is_dir($path)) {
            Artisan::call('migrate', [
                '--path' => str_replace(base_path().'/', '', $path),
                '--force' => true,
            ]);
        }
    }

    protected function tablesReady(): bool
    {
        try {
            return DB::connection()->getPdo() && Schema::hasTable('modules');
        } catch (Throwable) {
            return false;
        }
    }
}
