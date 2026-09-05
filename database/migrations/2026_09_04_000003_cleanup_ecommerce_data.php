<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Cleanup for existing installs after ecommerce removal: shop settings,
// /shop menu link, shop page_meta, modules.shop row, and orphaned
// products/orders permission rows (PermissionSyncer does not prune).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->whereIn('key', [
                'shop_location',
                'shop_currency',
                'shop_currency_symbol',
            ])->delete();
        }

        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('url', '/shop')->delete();
        }

        if (Schema::hasTable('page_metas')) {
            DB::table('page_metas')->where('route_name', 'shop.index')->delete();
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('key', 'shop')->delete();
        }

        if (Schema::hasTable('role_has_permissions') && Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')
                ->where('name', 'like', 'products.%')
                ->orWhere('name', 'like', 'orders.%')
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty — shop data is not restored on rollback.
    }
};
