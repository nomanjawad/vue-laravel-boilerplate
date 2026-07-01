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

            $target = $this->appendQueryString(
                $map[$path]['to'],
                $request->getQueryString(),
            );

            return redirect($target, $map[$path]['status']);
        }

        return $next($request);
    }

    /**
     * Merge an incoming request's query string onto a redirect target that
     * may already carry one — naïve concatenation produces `/foo?x=1?y=2`,
     * which browsers treat as part of the value.
     */
    private function appendQueryString(string $target, ?string $requestQuery): string
    {
        if (! $requestQuery) {
            return $target;
        }
        $separator = str_contains($target, '?') ? '&' : '?';

        return $target.$separator.$requestQuery;
    }
}
