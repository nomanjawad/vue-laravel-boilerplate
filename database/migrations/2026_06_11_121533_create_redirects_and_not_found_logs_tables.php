<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 191-char limit keeps unique/indexed utf8mb4 columns within the key
        // length supported by older MySQL/MariaDB found on shared hosting.
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 191)->unique();
            $table->string('to_path', 191);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->index();
            $table->string('referrer', 512)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('redirects');
    }
};
