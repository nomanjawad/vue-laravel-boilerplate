<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $headerMenus = [
            ['title' => 'Home', 'url' => '/', 'sort_order' => 1],
            ['title' => 'About', 'url' => '/about', 'sort_order' => 2],
            ['title' => 'Blog', 'url' => '/blog', 'sort_order' => 3],
            ['title' => 'Shop', 'url' => '/shop', 'sort_order' => 4],
            ['title' => 'Contact', 'url' => '/contact', 'sort_order' => 5],
        ];

        foreach ($headerMenus as $menu) {
            Menu::firstOrCreate(
                ['location' => 'header', 'title' => $menu['title']],
                array_merge($menu, ['location' => 'header', 'is_active' => true])
            );
        }

        $footerMenus = [
            ['title' => 'About', 'url' => '/about', 'sort_order' => 1],
            ['title' => 'Blog', 'url' => '/blog', 'sort_order' => 2],
            ['title' => 'Contact', 'url' => '/contact', 'sort_order' => 3],
            ['title' => 'Privacy Policy', 'url' => '/page/privacy-policy', 'sort_order' => 4],
            ['title' => 'Terms of Service', 'url' => '/page/terms-of-service', 'sort_order' => 5],
        ];

        foreach ($footerMenus as $menu) {
            Menu::firstOrCreate(
                ['location' => 'footer', 'title' => $menu['title']],
                array_merge($menu, ['location' => 'footer', 'is_active' => true])
            );
        }
    }
}
