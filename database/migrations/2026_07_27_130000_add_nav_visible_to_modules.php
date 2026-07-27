<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates "module enabled" from "module shown in the admin sidebar".
 *
 * Before this, a core module (which cannot be disabled) also could not be
 * hidden from the sidebar — an all-or-nothing coupling that forced projects
 * hand-editing config/modules.php to remove `page_metas` / `menus` /
 * `subscribers` from the nav. See feedback.md §11.
 *
 * After this:
 *   - `enabled`      — controls whether routes/migrations/seeders load.
 *   - `nav_visible`  — controls whether the module's manifest `nav` entries
 *                       show up in the sidebar.
 *
 * Core modules can toggle `nav_visible` off while remaining fully active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->boolean('nav_visible')->default(true)->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('nav_visible');
        });
    }
};
