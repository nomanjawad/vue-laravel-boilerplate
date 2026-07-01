<?php

use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\PageMetaController;
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

// Cache controls — admins only.
Route::post('cache/clear', [CacheController::class, 'clear'])
    ->middleware('can:settings.update')
    ->name('cache.clear');

// Module management — gated by a dedicated permission so non-admin roles
// (e.g. editor) can't accidentally toggle features off.
Route::prefix('modules')->name('modules.')->middleware('can:modules.manage')->group(function () {
    Route::get('/', [ModulesController::class, 'index'])->name('index');
    Route::post('{key}/enable', [ModulesController::class, 'enable'])->name('enable');
    Route::post('{key}/disable', [ModulesController::class, 'disable'])->name('disable');
    Route::post('{key}/reinstall', [ModulesController::class, 'reinstall'])->name('reinstall');
    Route::post('{key}/clear-health', [ModulesController::class, 'clearHealth'])->name('clear-health');
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
    Route::put('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
});
Route::middleware('can:menus.delete')->group(function () {
    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
});

// Page SEO.
Route::middleware('can:page_metas.view')->group(function () {
    Route::get('page-metas', [PageMetaController::class, 'index'])->name('page-metas.index');
});
Route::middleware('can:page_metas.create')->group(function () {
    Route::post('page-metas', [PageMetaController::class, 'store'])->name('page-metas.store');
});
Route::middleware('can:page_metas.update')->group(function () {
    Route::put('page-metas/{pageMeta}', [PageMetaController::class, 'update'])->name('page-metas.update');
});
Route::middleware('can:page_metas.delete')->group(function () {
    Route::delete('page-metas/{pageMeta}', [PageMetaController::class, 'destroy'])->name('page-metas.destroy');
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
Route::middleware('can:media.delete')->group(function () {
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
});
