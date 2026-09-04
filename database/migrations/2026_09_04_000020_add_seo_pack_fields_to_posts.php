<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.5 SEO pack — per-post canonical / OG overrides / focus keyword.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('canonical_url', 500)->nullable()->after('og_image');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('focus_keyword', 191)->nullable()->after('og_description');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['canonical_url', 'og_title', 'og_description', 'focus_keyword']);
        });
    }
};
