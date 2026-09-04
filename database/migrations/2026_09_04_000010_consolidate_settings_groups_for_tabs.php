<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Phase 7 settings consolidation:
 * - Move analytics-group keys into `seo` so group-driven tabs keep one
 *   "SEO & Analytics" tab (matching the previous hardcoded TABS layout).
 * - No Theme rows yet — SettingController force-includes an empty Theme
 *   tab as a Phase 8 placeholder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::whereIn('key', [
            'ga_measurement_id',
            'gtm_container_id',
            'cookie_consent_text',
        ])->update(['group' => 'seo']);
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'ga_measurement_id',
            'gtm_container_id',
            'cookie_consent_text',
        ])->update(['group' => 'analytics']);
    }
};
