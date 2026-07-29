<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the sitewide "No-index" toggle (Admin > Settings > SEO & Analytics).
 * SettingService::update() only UPDATEs existing rows, so a real row is
 * needed here or the first save would silently no-op — same reasoning as
 * the site_logo/site_favicon backfill migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'site_noindex'],
            ['value' => '', 'type' => 'boolean', 'group' => 'seo'],
        );
    }

    public function down(): void
    {
        Setting::where('key', 'site_noindex')->delete();
    }
};
