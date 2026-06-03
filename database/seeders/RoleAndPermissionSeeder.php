<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users', 'manage roles',
            'manage posts', 'manage categories', 'manage tags',
            'manage products', 'manage orders',
            'manage pages', 'manage menus', 'manage settings', 'manage media',
            'manage careers', 'manage case-studies', 'manage teams',
            'manage page-metas',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->syncPermissions([
            'manage posts', 'manage categories', 'manage tags',
            'manage products', 'manage media',
            'manage careers', 'manage case-studies', 'manage teams',
        ]);

        Role::firstOrCreate(['name' => 'user']);
    }
}
