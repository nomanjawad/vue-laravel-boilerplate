<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = ['key', 'value', 'type', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('site_settings', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget('site_settings');
    }
}
