<?php

use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ProfileController;
use App\Services\SitemapService;
use Illuminate\Support\Facades\Route;

// Static pages get full-response caching. Do NOT add 'responsecache' to any
// route that renders session-specific data (cart, auth pages, form results).
Route::middleware('responsecache')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [AboutController::class, 'index'])->name('about');
});

// XML sitemap from static routes + published content. Cached; busted by
// the ClearsResponseCache trait whenever content changes.
Route::get('/sitemap.xml', function (SitemapService $sitemap) {
    abort_unless(config('template.indexable'), 404);

    return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// Dynamic robots.txt: block all crawlers until SEO_INDEXABLE=true.
Route::get('/robots.txt', function () {
    $body = config('template.indexable')
        ? "User-agent: *\nDisallow:\n\nSitemap: ".url('/sitemap.xml')."\n"
        : "User-agent: *\nDisallow: /\n";

    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

if (config('template.features.contact_form')) {
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
}

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware(['throttle:5,1', 'doNotCacheResponse'])
    ->name('newsletter.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::get('/page/{page:slug}', [PageController::class, 'show'])
    ->middleware('responsecache')
    ->name('page.show');
