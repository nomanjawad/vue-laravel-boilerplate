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
        // Hydrate is_secret so the value accessor decrypts secrets (F11 #23).
        return Cache::remember('site_settings', 3600, function () {
            return Setting::query()
                ->get(['key', 'value', 'is_secret'])
                ->mapWithKeys(fn (Setting $row) => [$row->key => $row->value])
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function getByGroup(string $group): array
    {
        return Setting::query()
            ->where('group', $group)
            ->get(['key', 'value', 'is_secret'])
            ->mapWithKeys(fn (Setting $row) => [$row->key => $row->value])
            ->all();
    }

    public function update(array $settings): void
    {
        // Fetch + save per row so Eloquent mutators (secret encryption) and
        // model events (ClearsResponseCache, LogsContentActivity) fire.
        // Query-builder update() would store secrets plaintext and leave the
        // public response cache stale for up to 7 days.
        foreach ($settings as $key => $value) {
            $setting = Setting::query()->where('key', $key)->first();
            if (! $setting) {
                continue; // whitelist-by-existence
            }
            $setting->value = $value;
            $setting->save();
        }

        Cache::forget('site_settings');
    }
}
