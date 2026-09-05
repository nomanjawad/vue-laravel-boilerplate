<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated table for spatie/laravel-responsecache. Clearing the response
 * cache must not DELETE FROM the shared `cache` table (settings, modules,
 * sitemap, badges, …). See feedback.md F11 #8 / F12 #5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_responses', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_responses');
    }
};
