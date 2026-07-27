<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets a Content-Security-Policy header from config/csp.php.
 *
 * Off by default (CSP_ENABLED=false). When enabled, generates a per-request
 * nonce, expands any "'nonce'" placeholder in the configured directives to
 * "'nonce-{random}'", and injects the header. Templates can read the nonce
 * via csp_nonce() (a helper we register below) for inline scripts.
 *
 * Report-only mode (CSP_REPORT_ONLY=true) uses the -Report-Only header
 * variant, so browsers log violations without blocking — useful while
 * tuning the directive list.
 *
 * Register in bootstrap/app.php's ->withMiddleware() web(append: [...])
 * chain. Skips itself when CSP_ENABLED is false so the middleware costs
 * nothing when the feature isn't in use.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('csp.enabled')) {
            return $next($request);
        }

        $nonce = Str::random(24);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $headerName = config('csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($headerName, $this->build($nonce));

        return $response;
    }

    private function build(string $nonce): string
    {
        $parts = [];
        foreach ((array) config('csp.directives', []) as $directive => $sources) {
            $expanded = array_map(
                fn (string $s) => $s === "'nonce'" ? "'nonce-{$nonce}'" : $s,
                (array) $sources,
            );
            $parts[] = $directive.' '.implode(' ', $expanded);
        }

        return implode('; ', $parts);
    }
}
