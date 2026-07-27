<?php

namespace App\Console\Commands;

use App\Modules\Core\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pre-deploy + post-clone health check. Catches the breakage classes that
 * silently bite in production: missing PHP extensions, wrong DB credentials,
 * broken storage symlink, missing Vite manifest, queue misconfig, modules in
 * an unhealthy state. Every failure prints the exact fix.
 *
 *   php artisan template:doctor              # local dev preflight
 *   php artisan template:doctor --production # stricter — fails the deploy
 */
class TemplateDoctor extends Command
{
    protected $signature = 'template:doctor
                            {--production : Stricter checks for production deploy}
                            {--exit-zero : Always return exit code 0 even if checks fail — use in post-deploy health-report step so a failing check does not abort the deploy pipeline}';

    protected $description = 'Run pre-deploy + post-clone health checks. Catches the things that silently break in production.';

    protected int $failures = 0;

    public function handle(): int
    {
        $isProd = (bool) $this->option('production');

        $this->info('webTemplate doctor');
        $this->line('==================');
        $this->line('');

        $this->checkPhp($isProd);
        $this->checkExtensions();
        $this->checkEnv();
        $this->checkDatabase();
        $this->checkStorage();
        $this->checkViteManifest($isProd);
        $this->checkQueue($isProd);
        $this->checkScheduler();
        $this->checkBackupBinary();
        $this->checkModuleHealth();
        $this->checkRoutesOptimize();

        $this->line('');
        if ($this->failures === 0) {
            $this->info('All checks passed.');

            return self::SUCCESS;
        }

        $this->error("{$this->failures} check(s) failed. Address the items above.");

        if ($this->option('exit-zero')) {
            $this->line('(--exit-zero set: returning 0 so this post-deploy report does not abort the pipeline.)');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    protected function checkPhp(bool $isProd): void
    {
        $required = $isProd ? '8.3' : '8.3';
        $actual = PHP_VERSION;

        if (version_compare($actual, $required, '>=')) {
            $this->ok("PHP {$actual}");
        } else {
            $this->failed("PHP {$actual} (need >= {$required})", "Install PHP {$required}+ or set the cPanel PHP selector.");
        }
    }

    protected function checkExtensions(): void
    {
        foreach (['gd', 'pdo_mysql', 'openssl', 'mbstring', 'bcmath', 'intl', 'zip', 'curl'] as $ext) {
            if (extension_loaded($ext)) {
                $this->ok("ext: {$ext}");
            } else {
                $this->failed("ext: {$ext} missing", "Enable the {$ext} extension in php.ini (or the cPanel PHP selector).");
            }
        }
    }

    protected function checkEnv(): void
    {
        if (! file_exists(base_path('.env'))) {
            $this->failed('.env missing', 'Run `cp .env.example .env && php artisan key:generate`.');

            return;
        }
        $this->ok('.env present');

        foreach (['APP_KEY', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
            if (! env($key)) {
                $this->failed("{$key} not set in .env", "Set {$key}=… in .env and run `php artisan config:clear`.");
            }
        }
    }

    protected function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $this->ok('database reachable');
        } catch (Throwable $e) {
            $this->failed('database unreachable: '.$e->getMessage(), 'Verify DB_HOST/PORT/DATABASE/USERNAME/PASSWORD in .env. On MAMP: port 8889, root/root.');

            return;
        }

        if (Schema::hasTable('migrations')) {
            $this->ok('migrations table present');
        } else {
            $this->failed('migrations table missing', 'Run `php artisan migrate --force`.');
        }
    }

    protected function checkStorage(): void
    {
        $path = storage_path('app');
        if (is_writable($path)) {
            $this->ok('storage writable');
        } else {
            $this->failed("storage not writable: {$path}", "Run `chmod -R 775 storage bootstrap/cache` (cPanel: 755).");
        }

        $symlink = public_path('storage');
        if (is_link($symlink) || is_dir($symlink)) {
            $this->ok('public/storage symlink present');
        } else {
            $this->failed('public/storage symlink missing', 'Run `php artisan storage:link`.');
        }
    }

    protected function checkViteManifest(bool $isProd): void
    {
        $manifest = public_path('build/manifest.json');
        if (file_exists($manifest)) {
            $this->ok('Vite manifest built');
        } elseif ($isProd) {
            $this->failed('Vite manifest missing', 'Run `pnpm install && pnpm build` before deploying.');
        } else {
            $this->warn('  Vite manifest missing (ok in dev — run `pnpm dev`)');
        }

        if (file_exists(public_path('hot'))) {
            if ($isProd) {
                $this->failed('public/hot file present in production', 'Delete public/hot — it tells Vue to look for the dev server.');
            } else {
                $this->ok('public/hot present (dev server)');
            }
        }
    }

    protected function checkQueue(bool $isProd): void
    {
        $driver = config('queue.default');
        if (in_array($driver, ['database', 'redis', 'sqs', 'sync'], true)) {
            $this->ok("queue driver: {$driver}");
        } else {
            $this->failed("unknown queue driver: {$driver}", 'Set QUEUE_CONNECTION=database in .env (recommended for shared hosting).');
        }

        if ($driver === 'database') {
            try {
                if (! Schema::hasTable('jobs')) {
                    $this->failed('jobs table missing', 'Run `php artisan queue:table && php artisan migrate --force`.');
                }
            } catch (Throwable) {
                // DB unreachable — already reported by checkDatabase().
            }
        }
    }

    protected function checkScheduler(): void
    {
        $this->line('  scheduler  add this cron line on shared hosting:');
        $this->line('             <comment>* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1</comment>');
    }

    protected function checkBackupBinary(): void
    {
        $path = env('DB_DUMP_BINARY_PATH');
        if (! $path) {
            $this->warn('  DB_DUMP_BINARY_PATH not set (backups will fall back to the default PATH)');

            return;
        }
        if (is_executable($path)) {
            $this->ok("mysqldump at {$path}");
        } else {
            $this->failed("DB_DUMP_BINARY_PATH not executable: {$path}", 'On MAMP: /Applications/MAMP/Library/bin/mysql80/bin/mysqldump');
        }
    }

    protected function checkModuleHealth(): void
    {
        try {
            if (! Schema::hasTable('modules')) {
                $this->warn('  modules registry not yet migrated (run `php artisan migrate --force`)');

                return;
            }
            $manager = app(ModuleManager::class);
            $bad = collect($manager->summary())->where('unhealthy', true);
            if ($bad->isEmpty()) {
                $this->ok('all modules healthy');
            } else {
                foreach ($bad as $m) {
                    $this->failed("module unhealthy: {$m['key']}", "Check the error in /admin/modules and run `php artisan optimize:clear`. Last error: {$m['last_error']}");
                }
            }
        } catch (Throwable $e) {
            $this->warn('  module health check skipped: '.$e->getMessage());
        }
    }

    protected function checkRoutesOptimize(): void
    {
        try {
            $exit = Artisan::call('optimize');
            if ($exit === 0) {
                $this->ok('php artisan optimize clean');
            } else {
                $this->failed('php artisan optimize failed', 'Run `php artisan optimize` and read the error — usually a duplicate route name or view-compile issue.');
            }
        } catch (Throwable $e) {
            $this->failed('php artisan optimize threw: '.$e->getMessage(), 'Run `php artisan route:list` to find the offending route.');
        }
    }

    protected function ok(string $msg): void { $this->line('  <fg=green>✓</> '.$msg); }

    protected function failed(string $msg, string $fix): void
    {
        $this->failures++;
        $this->line('  <fg=red>✗</> '.$msg);
        $this->line('       <comment>fix:</comment> '.$fix);
    }
}
