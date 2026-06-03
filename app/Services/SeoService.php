<?php

namespace App\Services;

use App\Models\PageMeta;

class SeoService
{
    public function getMetaForRoute(string $routeName): array
    {
        $meta = PageMeta::where('route_name', $routeName)->first();

        if (! $meta) {
            return [];
        }

        return [
            'title' => $meta->meta_title,
            'description' => $meta->meta_description,
            'og_image' => $meta->og_image,
        ];
    }
}
