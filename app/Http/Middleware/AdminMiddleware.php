<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasAnyRole(['admin', 'editor'])) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
