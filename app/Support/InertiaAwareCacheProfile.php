<?php

namespace App\Support;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

/**
 * Keeps Inertia (XHR/JSON) and full-page (HTML) requests in SEPARATE cache
 * entries.
 *
 * Bug this fixes (feedback.md #1):
 *   Spatie\laravel-responsecache's DefaultHasher keys the cache on
 *   host + URI + method only — NOT the X-Inertia header. Whichever request
 *   type warms `/` first (full-page HTML or an Inertia XHR expecting JSON)
 *   is served to BOTH. When cached HTML is served to an Inertia XHR, the
 *   Inertia client renders it inside a sandboxed <iframe srcdoc> "invalid
 *   response" modal → CORS-blocked bundle → dead navigation.
 *
 * This profile appends `-inertia-{version}-{partial-component}-{partial-data}`
 * to the cache-name suffix when X-Inertia is present, so the two request
 * types can never share a cache entry. Full-page and partial-reload Inertia
 * requests also get distinct suffixes because Inertia may send only some
 * shared props during a partial reload (`Inertia::optional(...)`).
 */
class InertiaAwareCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function useCacheNameSuffix(Request $request): string
    {
        $suffix = parent::useCacheNameSuffix($request);

        if ($request->headers->has('X-Inertia')) {
            $suffix .= '-inertia'
                .'-'.$request->header('X-Inertia-Version', '')
                .'-'.$request->header('X-Inertia-Partial-Component', '')
                .'-'.$request->header('X-Inertia-Partial-Data', '');
        }

        return $suffix;
    }
}
