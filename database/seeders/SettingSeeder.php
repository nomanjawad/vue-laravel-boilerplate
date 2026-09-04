<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\BrandPalette;
use App\Support\Theme;
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
            ['key' => 'contact_default_country', 'value' => 'BD', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'address', 'value' => '123 Main Street, City, Country', 'type' => 'text', 'group' => 'contact'],
            // Social Media tab.
            ['key' => 'whatsapp', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'facebook', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'twitter', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'instagram', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'linkedin', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'youtube', 'value' => '', 'type' => 'string', 'group' => 'social'],
            // SEO & Analytics tab. Analytics scripts load only after cookie-consent acceptance.
            ['key' => 'og_image', 'value' => '', 'type' => 'image', 'group' => 'seo'],
            ['key' => 'site_noindex', 'value' => '', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'seo_title_template', 'value' => '%title% — %site_name%', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_enabled', 'value' => '', 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'local_business_type', 'value' => 'LocalBusiness', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_street', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_city', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_region', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_postal', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_country', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_lat', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_lng', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'local_business_opening_hours', 'value' => '', 'type' => 'text', 'group' => 'seo'],
            ['key' => 'ga_measurement_id', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'gtm_container_id', 'value' => '', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'cookie_consent_text', 'value' => '', 'type' => 'text', 'group' => 'seo'],
            // Theme tab (admin + public CSS tokens via app.blade.php).
            ['key' => 'theme_primary_color', 'value' => BrandPalette::DEFAULT_PRIMARY, 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_font', 'value' => Theme::DEFAULT_FONT, 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_radius', 'value' => Theme::DEFAULT_RADIUS, 'type' => 'string', 'group' => 'theme'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
