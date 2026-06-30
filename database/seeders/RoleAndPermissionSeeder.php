<?php

namespace Database\Seeders;

use App\Modules\Core\PermissionSyncer;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Permission matrix lives in module manifests (config/modules.php + every
 * physical module's module.php). This seeder just ensures roles exist, then
 * delegates the actual sync to PermissionSyncer — which reads every enabled
 * module's `permissions` block and flattens it into Spatie permission rows.
 *
 * Idempotent: safe to run on every deploy, and ModuleManager::enable() calls
 * the same syncer so toggling a module from the dashboard keeps permissions
 * in step.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Roles must exist before we can attach permissions to them.
        foreach (['super-admin', 'admin', 'editor', 'user'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // PermissionSyncer reads every enabled module's manifest and syncs.
        app(PermissionSyncer::class)->sync();
    }
}
