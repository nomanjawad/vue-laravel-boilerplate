<?php

use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\ProfileController;
use App\Services\SitemapService;
use Illuminate\Support\Facades\Route;

// RankMath-style sitemap index + per-type children. Cached; busted by
// SitemapService::forgetAll() from ClearsResponseCache / JsonDataService.
Route::get('/sitemap.xml', function (SitemapService $sitemap) {
    abort_unless(config('template.indexable'), 404);

    return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

Route::get('/sitemap-{type}.xml', function (string $type, SitemapService $sitemap) {
    abort_unless(config('template.indexable'), 404);
    abort_unless(isset(SitemapService::CHILD_KEYS[$type]), 404);

    $xml = $sitemap->childXml($type);
    abort_if($xml === null, 404);

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->where('type', 'pages|posts|categories|careers|case-studies')->name('sitemap.child');

// Dynamic robots.txt: block all crawlers until SEO_INDEXABLE=true.
Route::get('/robots.txt', function () {
    $body = config('template.indexable')
        ? "User-agent: *\nDisallow:\n\nSitemap: ".url('/sitemap.xml')."\n"
        : "User-agent: *\nDisallow: /\n";

    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// Contact GET is served by DynamicPageController (data/pages/contact.json).
// POST stays here behind the feature flag.
if (config('template.features.contact_form')) {
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
}

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware(['throttle:5,1', 'doNotCacheResponse'])
    ->name('newsletter.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
