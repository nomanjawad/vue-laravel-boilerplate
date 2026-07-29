<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General tab.
            ['key' => 'site_name', 'value' => 'WebTemplate', 'type' => 'string', 'group' => 'general'],
            // Doubles as the SEO meta-description default (see SeoService).
            ['key' => 'site_description', 'value' => 'A modern web template for building amazing websites.', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => '', 'type' => 'image', 'group' => 'general'],
            ['key' => 'site_favicon', 'value' => '', 'type' => 'image', 'group' => 'general'],
            // Contact Information tab.
            ['key' => 'contact_email', 'value' => 'info@example.com', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'contact_phone', 'value' => '+1 234 567 890', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'address', 'value' => '123 Main Street, City, Country', 'type' => 'text', 'group' => 'contact'],
            // Social Media tab.
            ['key' => 'whatsapp', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'facebook', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'twitter', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'instagram', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'linkedin', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'youtube', 'value' => '', 'type' => 'string', 'group' => 'social'],
            // Shop Settings tab.
            ['key' => 'shop_location', 'value' => '', 'type' => 'string', 'group' => 'shop'],
            ['key' => 'shop_currency', 'value' => 'USD', 'type' => 'string', 'group' => 'shop'],
            ['key' => 'shop_currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'shop'],
            // SEO & Analytics tab. Analytics scripts load only after cookie-consent acceptance.
            ['key' => 'og_image', 'value' => '', 'type' => 'image', 'group' => 'seo'],
            ['key' => 'ga_measurement_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'gtm_container_id', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'cookie_consent_text', 'value' => '', 'type' => 'text', 'group' => 'analytics'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
