<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends an `X-Robots-Tag: noindex, nofollow` header on every response while
 * the site is not marked indexable (config: template.indexable / SEO_INDEXABLE).
 *
 * This is the most authoritative way to keep a staging/template site out of
 * search results: unlike a client-rendered meta tag, the header is present in
 * the raw HTTP response, so crawlers see it without executing JavaScript.
 */
class PreventSearchIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('template.indexable')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
