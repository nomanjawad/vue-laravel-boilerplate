<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\LogsContentActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Setting extends Model
{
    use ClearsResponseCache;
    use LogsContentActivity;

    protected $table = 'site_settings';

    protected $fillable = ['key', 'value', 'type', 'group', 'is_secret'];

    protected $casts = [
        'is_secret' => 'bool',
    ];

    /**
     * Encrypt the value at rest when is_secret is true. Existing plaintext
     * secrets from before the migration are read back as-is (decryptString
     * throws on non-encrypted input; we catch and return the raw value so
     * a mid-migration project doesn't lose data).
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function (?string $raw): ?string {
                if ($raw === null || $raw === '') {
                    return $raw;
                }
                if (! ($this->attributes['is_secret'] ?? false)) {
                    return $raw;
                }
                try {
                    return Crypt::decryptString($raw);
                } catch (Throwable) {
                    // Legacy plaintext secret from before the is_secret migration.
                    // Callers get the raw value; next save re-writes as ciphertext.
                    return $raw;
                }
            },
            set: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return $value;
                }
                if (! ($this->attributes['is_secret'] ?? false)) {
                    return $value;
                }

                return Crypt::encryptString($value);
            },
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // IMPORTANT: only cache plain arrays here — Laravel Collections/models do not
        // round-trip reliably through the file cache driver used on shared hosting.
        // NOTE: Secret values are stored ciphertext in DB but returned decrypted here
        // because pluck() reads the accessor path only for the primary column. For
        // secret keys use Setting::query()->where('key', ...)->first()->value instead.
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
