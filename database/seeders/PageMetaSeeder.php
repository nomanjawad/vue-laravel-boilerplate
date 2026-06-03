<?php

namespace Database\Seeders;

use App\Models\PageMeta;
use Illuminate\Database\Seeder;

class PageMetaSeeder extends Seeder
{
    public function run(): void
    {
        $metas = [
            ['route_name' => 'home', 'meta_title' => 'Home', 'meta_description' => 'Welcome to our website.'],
            ['route_name' => 'about', 'meta_title' => 'About Us', 'meta_description' => 'Learn more about our company.'],
            ['route_name' => 'contact', 'meta_title' => 'Contact Us', 'meta_description' => 'Get in touch with us.'],
            ['route_name' => 'blog.index', 'meta_title' => 'Blog', 'meta_description' => 'Read our latest articles.'],
            ['route_name' => 'shop.index', 'meta_title' => 'Shop', 'meta_description' => 'Browse our products.'],
        ];

        foreach ($metas as $meta) {
            PageMeta::firstOrCreate(
                ['route_name' => $meta['route_name']],
                $meta
            );
        }
    }
}
