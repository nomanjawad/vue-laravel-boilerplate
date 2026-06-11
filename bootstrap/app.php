<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\PreventSearchIndexing;
use App\Models\NotFoundLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;
use Symfony\Component\HttpFoundation\Response;

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
        // Redirect lookup must be GLOBAL (not in the web group): group middleware
        // only runs after a route matches, so it would never see legacy URLs
        // that don't exist any more — exactly the ones redirects are for.
        $middleware->prepend(HandleRedirects::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Discourage search engines until SEO_INDEXABLE=true (e.g. in production).
        $middleware->append(PreventSearchIndexing::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            // Full-response cache for static public pages (spatie/laravel-responsecache).
            // Never apply to pages that render session data (cart, auth, forms with errors).
            'responsecache' => CacheResponse::class,
            'doNotCacheResponse' => DoNotCacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Render branded Inertia error pages in production (resources/js/Pages/Error.vue).
        // In local dev the default Laravel error screens stay visible for debugging.
        // If the app is broken too early to render Inertia (e.g. missing Vite manifest),
        // the static fallbacks in resources/views/errors/ are used by Laravel instead.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            // 404 monitoring: aggregate missed URLs (with referrer) so the admin
            // dashboard can suggest redirects. (NotFoundHttpException is in
            // Laravel's dont-report list, so this lives here, not in report().)
            if ($status === 404 && $request->isMethod('GET') && Schema::hasTable('not_found_logs')) {
                rescue(fn () => NotFoundLog::record(
                    '/'.ltrim($request->path(), '/'),
                    $request->headers->get('referer'),
                    $request->userAgent(),
                ), report: false);
            }

            if (! app()->environment(['local', 'testing']) && in_array($status, [403, 404, 429, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            if ($status === 419) {
                return back()->with('error', 'The page expired, please try again.');
            }

            return $response;
        });
    })->create();
