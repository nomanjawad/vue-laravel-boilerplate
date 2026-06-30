<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            // 64 is comfortably under the 191 utf8mb4 unique-index ceiling
            // (v2 convention — old shared-hosting MySQL).
            $table->string('key', 64)->unique();
            $table->string('type', 16)->default('virtual'); // 'virtual' | 'physical'
            $table->boolean('enabled')->default(true);
            $table->string('installed_version', 32)->nullable();
            $table->boolean('unhealthy')->default(false);
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            // settings: JSON is nullable (no default — old MySQL doesn't allow JSON defaults).
            $table->json('settings')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
