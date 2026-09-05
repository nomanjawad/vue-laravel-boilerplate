<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Memoize Schema::hasTable() — information_schema probes are expensive and
 * were fired dozens of times per uncached public request (F12 #4).
 *
 * Request-local static + optional 1h cache. Table presence rarely changes
 * outside migrate; a stale "missing" for ≤1h after a migrate is acceptable.
 */
final class SchemaCache
{
    /** @var array<string, bool> */
    private static array $tables = [];

    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tables)) {
            return self::$tables[$table];
        }

        if (app()->bound('cache')) {
            try {
                return self::$tables[$table] = (bool) Cache::remember(
                    'schema.has_table.'.$table,
                    3600,
                    fn () => Schema::hasTable($table),
                );
            } catch (Throwable) {
                // Cache unavailable mid-boot — fall through to live probe.
            }
        }

        return self::$tables[$table] = Schema::hasTable($table);
    }

    /** Drop memo for tests / after migrate in the same process. */
    public static function flush(?string $table = null): void
    {
        if ($table === null) {
            self::$tables = [];

            return;
        }

        unset(self::$tables[$table]);
        if (app()->bound('cache')) {
            Cache::forget('schema.has_table.'.$table);
        }
    }
}
