<?php

namespace App\Modules\Core;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sync the {resource}.{action} permission grid from every enabled module's
 * manifest into the Spatie permission tables. Idempotent — safe to call on
 * every enable/disable + as part of the seeder.
 */
class PermissionSyncer
{
    public function __construct(protected ModuleManager $manager) {}

    public function sync(): void
    {
        $desired = [];
        foreach ($this->manager->manifests() as $key => $manifest) {
            if (! $this->manager->enabled($key)) {
                continue;
            }
            foreach ($manifest['permissions'] ?? [] as $resource => $actions) {
                foreach ($actions as $action) {
                    $desired[] = "{$resource}.{$action}";
                }
            }
        }

        $desired = array_values(array_unique($desired));

        foreach ($desired as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($desired);

        // Editor: view/create/update on content modules. Core modules are
        // excluded by default (users/settings/…), except media + page_content
        // — editors need the media library and the page widget editor.
        $coreResources = [];
        foreach ($this->manager->manifests() as $manifest) {
            if (($manifest['core'] ?? false) !== true) {
                continue;
            }
            foreach ($manifest['permissions'] ?? [] as $resource => $actions) {
                $coreResources[] = $resource;
            }
        }

        $editorCoreAllowlist = ['media', 'page_content'];

        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editorGrants = array_values(array_filter(
            $desired,
            function ($name) use ($coreResources, $editorCoreAllowlist) {
                $resource = explode('.', $name, 2)[0];

                if (in_array($resource, $coreResources, true)
                    && ! in_array($resource, $editorCoreAllowlist, true)) {
                    return false;
                }

                return str_ends_with($name, '.view')
                    || str_ends_with($name, '.create')
                    || str_ends_with($name, '.update');
            },
        ));
        $editor->syncPermissions($editorGrants);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function dropFor(array $manifest): void
    {
        $names = [];
        foreach ($manifest['permissions'] ?? [] as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$resource}.{$action}";
            }
        }

        if (! $names) {
            return;
        }

        Permission::whereIn('name', $names)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
