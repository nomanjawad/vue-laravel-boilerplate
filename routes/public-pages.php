<?php

use App\Http\Controllers\Public\DynamicPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dynamic JSON pages (data/pages/{slug}.json)
|--------------------------------------------------------------------------
|
| Registered LAST in bootstrap/app.php (after FileSystemPageRouter) so every
| explicit route and auto-router page wins over the /{slug} catch-all.
| The route definitions are static — route:cache stays valid when pages are
| created/deleted; the controller aborts 404 for missing/draft JSON.
*/

Route::middleware('responsecache')->group(function () {
    Route::get('/', [DynamicPageController::class, 'show'])
        ->defaults('slug', 'home')
        ->name('home');

    Route::get('/{slug}', [DynamicPageController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('page.show');
});
