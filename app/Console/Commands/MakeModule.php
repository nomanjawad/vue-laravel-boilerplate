<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Scaffold a new module folder at app/Modules/{Name}/ with the standard layout,
 * a manifest stub, and an empty ServiceProvider. Pair with make:crud to stamp
 * actual resources inside.
 */
class MakeModule extends Command
{
    protected $signature = 'make:module {name : StudlyCase module name, e.g. Blog or Newsletter} {--description= : Optional one-line description}';

    protected $description = 'Scaffold a new self-contained feature module under app/Modules/{Name}/';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $key = Str::snake($name);
        $description = $this->option('description') ?? '';

        $base = app_path("Modules/{$name}");
        if (is_dir($base)) {
            $this->error("Module [{$name}] already exists at {$base}.");

            return self::FAILURE;
        }

        // Standard module layout — matches what AbstractModuleServiceProvider expects.
        $dirs = [
            'Http/Controllers/Admin',
            'Http/Controllers/Public',
            'Http/Requests',
            'Models',
            'Policies',
            'Services',
            'Data',
            'Database/Migrations',
            'Database/Factories',
            'Database/Seeders',
            'Routes',
            'Views',
            'Resources/js/Pages/Admin',
            'Resources/js/Pages/Public',
            'Resources/js/Components',
            'Resources/css',
            'Tests/Feature',
        ];

        foreach ($dirs as $sub) {
            $path = "{$base}/{$sub}";
            mkdir($path, 0755, true);
            file_put_contents("{$path}/.gitkeep", '');
        }

        // Manifest.
        $manifest = $this->stub('module/module.php.stub', [
            'name' => $name,
            'key' => $key,
            'description' => $description,
        ]);
        file_put_contents("{$base}/module.php", $manifest);

        // ServiceProvider.
        $provider = $this->stub('module/ServiceProvider.php.stub', [
            'name' => $name,
            'key' => $key,
        ]);
        file_put_contents("{$base}/{$name}ModuleServiceProvider.php", $provider);

        // Empty Routes/ folder — `make:crud` stamps the real routes file per
        // resource (e.g. Routes/testimonials.php). No placeholder files.

        $this->info("Module [{$name}] scaffolded at app/Modules/{$name}/");
        $this->line('');
        $this->line('Next steps:');
        $this->line("  1. <comment>php artisan make:crud Thing --module={$name}</comment>");
        $this->line("  2. Enable the module from the admin dashboard at /admin/modules");
        $this->line('  3. Customize app/Modules/'.$name.'/module.php manifest');

        return self::SUCCESS;
    }

    protected function stub(string $relative, array $vars): string
    {
        $path = base_path("stubs/{$relative}");
        $content = file_get_contents($path);
        foreach ($vars as $k => $v) {
            $content = str_replace("{{{$k}}}", $v, $content);
        }

        return $content;
    }
}
