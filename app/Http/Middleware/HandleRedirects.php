<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-managed 301/302 redirects, checked before routing so old URLs from a
 * previous site (or renamed slugs) never reach the 404 handler. The lookup is
 * one cached array read per request — no DB query on the hot path.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || ! Schema::hasTable('redirects')) {
            return $next($request);
        }

        $path = Redirect::normalizePath($request->path());
        $map = Redirect::map();

        if (isset($map[$path])) {
            // Count the hit without busting the cached map (quiet update).
            Redirect::where('from_path', $path)->increment('hits');

            $query = $request->getQueryString();
            $target = $map[$path]['to'].($query ? '?'.$query : '');

            return redirect($target, $map[$path]['status']);
        }

        return $next($request);
    }
}
