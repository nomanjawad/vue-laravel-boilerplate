<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\PreventSearchIndexing;
use App\Models\NotFoundLog;
use Illuminate\Database\QueryException;
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

            // Module route files are always registered so enable/disable from
            // /admin/modules is reflected immediately. The per-file `module:{key}`
            // middleware short-circuits with 404 when a module is disabled.
            // Each require is rescued so a broken module route file cannot
            // 500 the entire panel.
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function () {
                    require base_path('routes/admin.php');
                    rescue(fn () => require base_path('routes/admin-blog.php'));
                    rescue(fn () => require base_path('routes/admin-shop.php'));
                    rescue(fn () => require base_path('routes/admin-optional.php'));
                });

            Route::middleware('web')
                ->group(function () {
                    require base_path('routes/public.php');
                    rescue(fn () => require base_path('routes/public-blog.php'));
                    rescue(fn () => require base_path('routes/public-shop.php'));
                    rescue(fn () => require base_path('routes/public-optional.php'));

                    // Next.js-style file-system routing: for every
                    // resources/js/Pages/Public/{Folder}/Index.vue without a
                    // matching explicit route above, auto-register GET /kebab.
                    // Explicit routes always win, so all DB-bound pages
                    // (Home, About, Blog, Shop, etc.) keep their controllers.
                    // See App\Support\FileSystemPageRouter.
                    app(\App\Support\FileSystemPageRouter::class)->register(app('router'));
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
            // No-op when CSP_ENABLED=false (default). See config/csp.php.
            ContentSecurityPolicy::class,
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

        // Optional Sentry integration — opt-in. Set SENTRY_LARAVEL_DSN in .env
        // and run `composer require sentry/sentry-laravel` (already in
        // composer.json require). Guarded by class_exists so the template
        // still boots cleanly when the package isn't installed.
        if (class_exists(\Sentry\Laravel\Integration::class)) {
            $exceptions->reportable(function (Throwable $e) {
                \Sentry\Laravel\Integration::captureUnhandledException($e);
            });
        }

        // Friendly database-error page. Instead of a raw PDOException stack
        // trace, render resources/views/errors/database.blade.php with a
        // hint tailored to the SQLSTATE code (missing column, connection
        // refused, access denied, etc.). Only in production; local dev keeps
        // the debug stack so developers can copy the actual query.
        $exceptions->render(function (QueryException|\PDOException $e, Request $request) {
            if (app()->environment(['local', 'testing']) && config('app.debug')) {
                return null; // let Laravel's debug page render
            }

            $sqlState = $e instanceof QueryException
                ? ($e->errorInfo[0] ?? null)
                : $e->getCode();
            $driverCode = $e instanceof QueryException ? ($e->errorInfo[1] ?? null) : null;

            [$title, $hint] = match (true) {
                $sqlState === '42S22' => [
                    'The database schema is out of date.',
                    'A recent update added a column that hasn\'t been migrated yet. Run <code>php artisan migrate</code> on the server.',
                ],
                $sqlState === '42S02' => [
                    'The database is missing a table this page needs.',
                    'This usually means migrations haven\'t been run on this environment. Run <code>php artisan migrate</code>.',
                ],
                $sqlState === 'HY000' && (int) $driverCode === 2002 => [
                    'The database server isn\'t reachable.',
                    'Check <code>DB_HOST</code> / <code>DB_PORT</code> in <code>.env</code>. On local dev, make sure MAMP / MySQL is running.',
                ],
                $sqlState === '28000' || (int) $driverCode === 1045 => [
                    'The database rejected the app\'s credentials.',
                    'Verify <code>DB_USERNAME</code> / <code>DB_PASSWORD</code> in <code>.env</code> match a user that can access <code>DB_DATABASE</code>.',
                ],
                default => [
                    'A database query failed.',
                    'The application couldn\'t complete a database operation. If this persists, check <code>storage/logs/laravel.log</code>.',
                ],
            };

            return response()->view('errors.database', [
                'title' => $title,
                'hint' => $hint,
                'detail' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        });

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
