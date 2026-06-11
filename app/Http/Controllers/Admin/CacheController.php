<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

class CacheController extends Controller
{
    /**
     * Clear every runtime cache: full-page response cache, the application
     * cache (settings/menus/json data/sitemap), and compiled views.
     */
    public function clear()
    {
        ResponseCache::clear();
        Cache::flush();
        Artisan::call('view:clear');

        return back()->with('success', 'All caches cleared.');
    }
}
