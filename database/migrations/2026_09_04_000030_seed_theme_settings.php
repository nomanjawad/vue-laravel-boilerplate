<?php

use App\Models\Setting;
use App\Support\BrandPalette;
use App\Support\Theme;
use Illuminate\Database\Migrations\Migration;

/**
 * Phase 8 — Theme / color schemes.
 * Whitelist-by-existence: Settings saves no-op without these rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key' => 'theme_primary_color',
                'value' => BrandPalette::DEFAULT_PRIMARY,
                'type' => 'string',
                'group' => 'theme',
            ],
            [
                'key' => 'theme_font',
                'value' => Theme::DEFAULT_FONT,
                'type' => 'string',
                'group' => 'theme',
            ],
            [
                'key' => 'theme_radius',
                'value' => Theme::DEFAULT_RADIUS,
                'type' => 'string',
                'group' => 'theme',
            ],
        ];

        foreach ($rows as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'theme_primary_color',
            'theme_font',
            'theme_radius',
        ])->delete();
    }
};
