<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Module;
use Illuminate\Database\Seeder;

/**
 * Seed an initial row for every module declared in config/modules.php so the
 * `/admin/modules` page lists them straight away. Without this seeder, the
 * manager still works (it falls back to config('template.features.*')) but
 * the dashboard list would be empty until the first enable/disable click.
 */
class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('modules', []) as $key => $manifest) {
            $isCore = (bool) ($manifest['core'] ?? false);
            $flag = $manifest['feature_flag_fallback'] ?? $key;
            $defaultEnabled = $isCore || (bool) config("template.features.{$flag}", false);

            Module::updateOrCreate(
                ['key' => $key],
                [
                    'type' => 'virtual',
                    'enabled' => $defaultEnabled,
                    'unhealthy' => false,
                    'installed_version' => $manifest['version'] ?? '1.0.0',
                    'installed_at' => $defaultEnabled ? now() : null,
                ],
            );
        }
    }
}
