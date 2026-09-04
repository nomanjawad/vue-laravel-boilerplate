<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Phase 7.5 SEO pack — title template + LocalBusiness schema builder fields.
 * Whitelist-by-existence: Settings saves no-op without these rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key' => 'seo_title_template',
                'value' => '%title% — %site_name%',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_enabled',
                'value' => '',
                'type' => 'boolean',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_type',
                'value' => 'LocalBusiness',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_street',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_city',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_region',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_postal',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_country',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_lat',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_lng',
                'value' => '',
                'type' => 'string',
                'group' => 'seo',
            ],
            [
                'key' => 'local_business_opening_hours',
                'value' => '',
                'type' => 'text',
                'group' => 'seo',
            ],
        ];

        foreach ($rows as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'seo_title_template',
            'local_business_enabled',
            'local_business_type',
            'local_business_street',
            'local_business_city',
            'local_business_region',
            'local_business_postal',
            'local_business_country',
            'local_business_lat',
            'local_business_lng',
            'local_business_opening_hours',
        ])->delete();
    }
};
