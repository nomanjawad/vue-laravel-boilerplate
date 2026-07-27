<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a per-row "this is a secret" flag to site_settings.
 *
 * When true, App\Models\Setting encrypts value on write (via Crypt::encryptString
 * in the attribute mutator) and Admin\SettingController blanks the value before
 * returning it to Inertia. Prevents SMTP passwords, gateway secrets, and webhook
 * signing keys from leaking to the browser or sitting in cleartext in a DB dump.
 *
 * Existing rows stay non-secret (default false); no re-encryption of historical
 * data. To flip a setting to secret, an admin (or a future seeder) sets is_secret
 * = true and re-saves the value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('is_secret')->default(false)->after('group');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('is_secret');
        });
    }
};
