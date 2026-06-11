<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public function all(): array
    {
        // IMPORTANT: only cache plain arrays here — Laravel Collections/models do not
        // round-trip reliably through the file cache driver used on shared hosting.
        return Cache::remember('site_settings', 3600, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function getByGroup(string $group): array
    {
        return Setting::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public function update(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        Cache::forget('site_settings');
    }
}
