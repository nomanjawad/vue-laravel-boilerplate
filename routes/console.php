<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
