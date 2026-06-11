<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * One-command project setup for a fresh clone:
 *
 *   php artisan template:init
 *
 * Asks for the site name, which feature modules to enable (written to .env so
 * flags always match shipped routes), and the admin account; then migrates,
 * seeds, links storage, and generates IDE helpers. Replaces the manual
 * post-clone checklist.
 */
class TemplateInit extends Command
{
    protected $signature = 'template:init {--demo : Also seed realistic demo content}';

    protected $description = 'Interactive first-time setup: site name, feature flags, admin user, migrate + seed';

    private const FEATURES = [
        'FEATURE_BLOG' => 'Blog (posts, categories, tags)',
        'FEATURE_SHOP' => 'Shop (products, cart, checkout)',
        'FEATURE_TEAMS' => 'Team members',
        'FEATURE_CONTACT_FORM' => 'Contact form',
        'FEATURE_CAREERS' => 'Careers / job listings',
        'FEATURE_CASE_STUDIES' => 'Case studies',
    ];

    public function handle(): int
    {
        $this->info('WebTemplate project setup');
        $this->newLine();

        $siteName = text('Site name', default: config('app.name', 'WebTemplate'), required: true);

        $enabled = multiselect(
            'Which features should this project ship with?',
            self::FEATURES,
            default: ['FEATURE_BLOG', 'FEATURE_TEAMS', 'FEATURE_CONTACT_FORM'],
        );

        $adminEmail = text('Admin email', default: 'admin@example.com', required: true);
        $adminPassword = password('Admin password (min 8 chars)', required: true);

        if (strlen($adminPassword) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        // 1. Write APP_NAME + feature flags so config matches shipped routes.
        $this->updateEnv(array_merge(
            ['APP_NAME' => '"'.str_replace('"', '', $siteName).'"'],
            collect(self::FEATURES)->mapWithKeys(
                fn ($label, $flag) => [$flag => in_array($flag, $enabled, true) ? 'true' : 'false']
            )->all(),
        ));

        Artisan::call('config:clear');

        // 2. Database.
        $this->info('Running migrations and base seeders…');
        Artisan::call('migrate', ['--force' => true], $this->output);
        Artisan::call('db:seed', ['--force' => true], $this->output);

        if ($this->option('demo') || confirm('Seed realistic demo content (posts, products, team)?', default: true)) {
            Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true], $this->output);
        }

        // 3. Admin account + site name setting.
        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            ['name' => 'Admin', 'password' => Hash::make($adminPassword)],
        );
        $admin->syncRoles(['admin']);
        Setting::query()->where('key', 'site_name')->update(['value' => $siteName]);

        // 4. Storage symlink + IDE helpers (dev only).
        Artisan::call('storage:link');

        if (App::environment('local') && class_exists(IdeHelperServiceProvider::class)) {
            Artisan::call('ide-helper:generate');
            Artisan::call('ide-helper:models', ['--nowrite' => true]);
        }

        $this->newLine();
        $this->info("Done! Log in at /admin with {$adminEmail}.");
        $this->comment('Next: edit data/*.json for static page content, then `composer run dev`.');

        return self::SUCCESS;
    }

    /** Update or append keys in the project .env file. */
    private function updateEnv(array $values): void
    {
        $path = base_path('.env');
        $env = file_get_contents($path);

        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                $env .= "\n{$key}={$value}";
            }
        }

        file_put_contents($path, $env);
    }
}
