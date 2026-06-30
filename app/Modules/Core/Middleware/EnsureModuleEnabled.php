<?php

namespace App\Modules\Core\Middleware;

use App\Modules\Core\ModuleManager;
use Closure;
use Illuminate\Http\Request;

/**
 * Route-level guard for modules. Even if cached routes outlive a disable,
 * this middleware short-circuits to 404 so a disabled module's controllers
 * never execute. Usage:  `Route::middleware('module:blog')->...`.
 */
class EnsureModuleEnabled
{
    public function __construct(protected ModuleManager $manager) {}

    public function handle(Request $request, Closure $next, string $key)
    {
        if (! $this->manager->enabled($key)) {
            abort(404);
        }

        return $next($request);
    }
}
