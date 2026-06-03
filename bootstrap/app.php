<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function () {
                    require base_path('routes/admin.php');

                    if (config('template.features.blog')) {
                        require base_path('routes/admin-blog.php');
                    }
                    if (config('template.features.shop')) {
                        require base_path('routes/admin-shop.php');
                    }
                    if (config('template.features.careers') || config('template.features.case_studies') || config('template.features.teams')) {
                        require base_path('routes/admin-optional.php');
                    }
                });

            Route::middleware('web')
                ->group(function () {
                    require base_path('routes/public.php');

                    if (config('template.features.blog')) {
                        require base_path('routes/public-blog.php');
                    }
                    if (config('template.features.shop')) {
                        require base_path('routes/public-shop.php');
                    }
                    if (config('template.features.careers') || config('template.features.case_studies') || config('template.features.teams')) {
                        require base_path('routes/public-optional.php');
                    }
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
