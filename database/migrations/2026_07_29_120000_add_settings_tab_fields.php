<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfills the settings introduced by the tabbed Site Settings UI.
 *
 * New keys (site_logo, site_favicon, shop_location) need real rows because
 * SettingService::update() only UPDATEs existing rows (a deliberate
 * whitelist-by-existence — unknown keys posted from the form are ignored).
 * Without rows here, saving those fields on an already-migrated install
 * would silently no-op.
 *
 * Also normalises two existing rows so DB `group`/`type` stay meaningful:
 * og_image becomes an image field, whatsapp moves under the social group.
 */
return new class extends Migration
{
    public function up(): void
    {
        $new = [
            ['key' => 'site_logo', 'type' => 'image', 'group' => 'general'],
            ['key' => 'site_favicon', 'type' => 'image', 'group' => 'general'],
            ['key' => 'shop_location', 'type' => 'string', 'group' => 'shop'],
        ];

        foreach ($new as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => '', 'type' => $setting['type'], 'group' => $setting['group']],
            );
        }

        Setting::where('key', 'og_image')->update(['type' => 'image', 'group' => 'seo']);
        Setting::where('key', 'whatsapp')->update(['group' => 'social']);
    }

    public function down(): void
    {
        Setting::whereIn('key', ['site_logo', 'site_favicon', 'shop_location'])->delete();
        Setting::where('key', 'og_image')->update(['type' => 'string', 'group' => 'general']);
        Setting::where('key', 'whatsapp')->update(['group' => 'contact']);
    }
};
