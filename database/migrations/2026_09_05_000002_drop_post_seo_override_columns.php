<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical / OG overrides are derived automatically (feedback.md F1/F2).
 * Copy any leftover og_image onto featured_image when featured is empty,
 * then drop the override columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('posts', 'og_image')) {
            DB::table('posts')
                ->whereNotNull('og_image')
                ->where('og_image', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('featured_image')->orWhere('featured_image', '');
                })
                ->update(['featured_image' => DB::raw('og_image')]);
        }

        Schema::table('posts', function (Blueprint $table) {
            foreach (['canonical_url', 'og_title', 'og_description', 'og_image'] as $col) {
                if (Schema::hasColumn('posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('og_image')->nullable()->after('meta_description');
            $table->string('canonical_url', 500)->nullable()->after('og_image');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->string('og_description', 500)->nullable()->after('og_title');
        });
    }
};
