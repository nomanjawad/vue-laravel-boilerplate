<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class JsonDataService
{
    public function get(string $filename): array
    {
        $cacheKey = 'json_data_' . $filename;

        return Cache::remember($cacheKey, 3600, function () use ($filename) {
            $path = base_path("data/{$filename}.json");

            if (! file_exists($path)) {
                return [];
            }

            $content = file_get_contents($path);
            return json_decode($content, true) ?? [];
        });
    }

    public function clearCache(string $filename): void
    {
        Cache::forget('json_data_' . $filename);
    }

    public function clearAllCache(): void
    {
        $files = glob(base_path('data/*.json'));
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            Cache::forget('json_data_' . $name);
        }
    }
}
