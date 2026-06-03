<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'WebTemplate', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'A modern web template for building amazing websites.', 'type' => 'text', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'info@example.com', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'contact_phone', 'value' => '+1 234 567 890', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'address', 'value' => '123 Main Street, City, Country', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'whatsapp', 'value' => '', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'facebook', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'twitter', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'instagram', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'linkedin', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'youtube', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'shop_currency', 'value' => 'USD', 'type' => 'string', 'group' => 'shop'],
            ['key' => 'shop_currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'shop'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
