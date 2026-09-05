<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Shared hosting: add ONE cron entry in cPanel pointing at the scheduler:
|   * * * * * php /home/USER/webTemplate/artisan schedule:run >> /dev/null 2>&1
|
| Local dev with MAMP: mysqldump lives outside PATH; point the dump binary
| at MAMP in config/database.php (mysql connection 'dump' option) or use:
|   /Applications/MAMP/Library/bin/mysql80/bin
*/

// Nightly database backup to storage/app (spatie/laravel-backup, local disk).
Schedule::command('backup:run --only-db')->dailyAt('02:00');
Schedule::command('backup:clean')->dailyAt('03:00');

// Queue worker — drains the database queue once per minute. Pattern designed
// for shared hosting where a long-running `queue:work` daemon isn't possible:
// `--stop-when-empty` means each tick processes whatever's pending and exits.
Schedule::command('queue:work --queue=default --stop-when-empty --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Prune stale failed jobs every Sunday.
Schedule::command('queue:prune-failed --hours=336')->weekly()->sundays()->at('04:00');

// Laravel's database cache store does not self-prune expired rows. App cache
// (`cache`) and response cache (`cache_responses`) both need a daily sweep —
// weekly left expired rows lingering ~14 days vs a 7-day response TTL.
Schedule::call(static function (): void {
    $cutoff = now()->timestamp;
    DB::table('cache')->where('expiration', '<', $cutoff)->delete();
    if (Schema::hasTable('cache_responses')) {
        DB::table('cache_responses')->where('expiration', '<', $cutoff)->delete();
    }
})->daily()->at('04:30')->name('cache-prune-expired');

// Prune activity_log rows older than 180 days (spatie/laravel-activitylog).
Schedule::command('activitylog:clean --days=180')->daily()->at('05:00');
