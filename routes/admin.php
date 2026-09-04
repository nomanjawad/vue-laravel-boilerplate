<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\CustomCodeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\UserController;
use App\Modules\Core\Http\Controllers\ModulesController;
use Illuminate\Support\Facades\Route;

// Dashboard is open to anyone who can hit the admin panel — the panel itself
// already requires auth + admin role via the bootstrap middleware stack.
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Global admin search — any logged-in admin; results are permission-filtered.
Route::get('search', [SearchController::class, 'index'])->name('search.index');

// Notifications (in-app bell). Available to any logged-in admin.
Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
Route::post('notifications/{id}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
Route::post('notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');

// Audit log — permission-gated. Reads spatie/activitylog rows populated by
// LogsContentActivity trait (content CRUD) + LogAuthenticationActivity
// listener (login/logout/failed).
Route::get('audit-log', [AuditLogController::class, 'index'])
    ->middleware('can:audit_log.view')
    ->name('audit-log.index');

// Cache panel — per-layer clears (never Cache::flush). Dashboard shortcut
// posts to system/cache/pages; the full UI lives at GET system/cache.
Route::middleware('can:settings.update')->group(function () {
    Route::get('system/cache', [CacheController::class, 'index'])->name('cache.index');
    Route::post('system/cache/{layer}', [CacheController::class, 'clear'])
        ->where('layer', 'pages|sitemap|settings|modules|redirects|views|all')
        ->name('cache.clear');
});

// Module management — gated by a dedicated permission so non-admin roles
// (e.g. editor) can't accidentally toggle features off.
Route::prefix('modules')->name('modules.')->middleware('can:modules.manage')->group(function () {
    Route::get('/', [ModulesController::class, 'index'])->name('index');
    Route::post('{key}/enable', [ModulesController::class, 'enable'])->name('enable');
    Route::post('{key}/disable', [ModulesController::class, 'disable'])->name('disable');
    Route::post('{key}/reinstall', [ModulesController::class, 'reinstall'])->name('reinstall');
    Route::post('{key}/clear-health', [ModulesController::class, 'clearHealth'])->name('clear-health');
    // Sidebar visibility toggle — independent of enable/disable so core
    // modules (which can't be disabled) can still be hidden from the nav.
    Route::post('{key}/toggle-nav', [ModulesController::class, 'toggleNav'])->name('toggle-nav');
    Route::delete('{key}', [ModulesController::class, 'uninstall'])->name('uninstall');
});

// Users (core module).
Route::middleware('can:users.view')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
});
Route::middleware('can:users.create')->group(function () {
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
});
Route::middleware('can:users.update')->group(function () {
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
});
Route::middleware('can:users.delete')->group(function () {
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Settings.
Route::get('settings', [SettingController::class, 'index'])
    ->middleware('can:settings.view')
    ->name('settings.index');
Route::put('settings', [SettingController::class, 'update'])
    ->middleware('can:settings.update')
    ->name('settings.update');

// Menus.
Route::middleware('can:menus.view')->group(function () {
    Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
});
Route::middleware('can:menus.create')->group(function () {
    Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
});
Route::middleware('can:menus.update')->group(function () {
    // reorder before {menu} so "reorder" is not captured as an id.
    Route::put('menus/reorder', [MenuController::class, 'reorder'])->name('menus.reorder');
    Route::put('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
});
Route::middleware('can:menus.delete')->group(function () {
    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
});

// Pages (data/pages/{slug}.json) + Header/Footer layout (data/header.json, footer.json).
Route::middleware('can:page_content.view')->group(function () {
    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('page-content/layout', [PageContentController::class, 'layout'])->name('page-content.layout');
});
Route::middleware('can:page_content.create')->group(function () {
    Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('pages', [PageController::class, 'store'])->name('pages.store');
});
Route::middleware('can:page_content.view')->group(function () {
    Route::get('pages/{slug}/edit', [PageController::class, 'edit'])
        ->where('slug', '[a-z0-9-]+')
        ->name('pages.edit');
});
Route::middleware('can:page_content.update')->group(function () {
    Route::put('pages/{slug}', [PageController::class, 'update'])
        ->where('slug', '[a-z0-9-]+')
        ->name('pages.update');
    Route::put('page-content/{file}', [PageContentController::class, 'update'])
        ->where('file', 'header|footer')
        ->name('page-content.update');
});
Route::middleware('can:page_content.delete')->group(function () {
    Route::delete('pages/{slug}', [PageController::class, 'destroy'])
        ->where('slug', '[a-z0-9-]+')
        ->name('pages.destroy');
});

// Redirects.
Route::middleware('can:redirects.view')->group(function () {
    Route::get('redirects', [RedirectController::class, 'index'])->name('redirects.index');
});
Route::middleware('can:redirects.create')->group(function () {
    Route::post('redirects', [RedirectController::class, 'store'])->name('redirects.store');
});
Route::middleware('can:redirects.update')->group(function () {
    Route::put('redirects/{redirect}', [RedirectController::class, 'update'])->name('redirects.update');
});
Route::middleware('can:redirects.delete')->group(function () {
    Route::delete('redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy');
});

// Custom Code — HTML/JS/CSS snippets injected into app.blade.php at
// head/body_start/body_end (see App\Services\CustomCodeService).
Route::middleware('can:custom_code.view')->group(function () {
    Route::get('custom-code', [CustomCodeController::class, 'index'])->name('custom-code.index');
});
Route::middleware('can:custom_code.create')->group(function () {
    Route::get('custom-code/create', [CustomCodeController::class, 'create'])->name('custom-code.create');
    Route::post('custom-code', [CustomCodeController::class, 'store'])->name('custom-code.store');
});
Route::middleware('can:custom_code.update')->group(function () {
    Route::get('custom-code/{customCode}/edit', [CustomCodeController::class, 'edit'])->name('custom-code.edit');
    Route::put('custom-code/{customCode}', [CustomCodeController::class, 'update'])->name('custom-code.update');
    Route::patch('custom-code/{customCode}/toggle', [CustomCodeController::class, 'toggle'])->name('custom-code.toggle');
});
Route::middleware('can:custom_code.delete')->group(function () {
    Route::delete('custom-code/{customCode}', [CustomCodeController::class, 'destroy'])->name('custom-code.destroy');
});

// Enquiries (contact-form leads).
Route::middleware('can:enquiries.view')->group(function () {
    Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
});
Route::middleware('can:enquiries.update')->group(function () {
    Route::post('enquiries/{enquiry}/read', [EnquiryController::class, 'markRead'])->name('enquiries.read');
    Route::post('enquiries/{enquiry}/unread', [EnquiryController::class, 'markUnread'])->name('enquiries.unread');
    Route::post('enquiries/bulk-read', [EnquiryController::class, 'bulkMarkRead'])->name('enquiries.bulk-read');
});
Route::middleware('can:enquiries.delete')->group(function () {
    Route::delete('enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');
});

// Subscribers.
Route::middleware('can:subscribers.view')->group(function () {
    Route::get('subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('subscribers/export', [SubscriberController::class, 'export'])->name('subscribers.export');
});
Route::middleware('can:subscribers.delete')->group(function () {
    Route::delete('subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');
});

// Media.
Route::middleware('can:media.view')->group(function () {
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
});
Route::middleware('can:media.create')->group(function () {
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
});
Route::middleware('can:media.update')->group(function () {
    Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
});
Route::middleware('can:media.delete')->group(function () {
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
});
