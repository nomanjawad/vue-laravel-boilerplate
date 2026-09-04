<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * page_metas was retired when SEO moved into data/*.json (and now
 * data/pages/{slug}.json). Drop the unused table for existing installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('page_metas');
    }

    public function down(): void
    {
        Schema::create('page_metas', function (Blueprint $table) {
            $table->id();
            $table->string('route_name')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->timestamps();
        });
    }
};
