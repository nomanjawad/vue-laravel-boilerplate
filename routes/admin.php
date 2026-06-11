<?php

use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageMetaController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');

Route::resource('users', UserController::class)->except('show');

Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
Route::post('menus', [MenuController::class, 'store'])->name('menus.store');
Route::put('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

Route::get('page-metas', [PageMetaController::class, 'index'])->name('page-metas.index');
Route::post('page-metas', [PageMetaController::class, 'store'])->name('page-metas.store');
Route::put('page-metas/{pageMeta}', [PageMetaController::class, 'update'])->name('page-metas.update');
Route::delete('page-metas/{pageMeta}', [PageMetaController::class, 'destroy'])->name('page-metas.destroy');

Route::get('redirects', [RedirectController::class, 'index'])->name('redirects.index');
Route::post('redirects', [RedirectController::class, 'store'])->name('redirects.store');
Route::put('redirects/{redirect}', [RedirectController::class, 'update'])->name('redirects.update');
Route::delete('redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy');

Route::get('subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
Route::get('subscribers/export', [SubscriberController::class, 'export'])->name('subscribers.export');
Route::delete('subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');

Route::get('media', [MediaController::class, 'index'])->name('media.index');
Route::post('media', [MediaController::class, 'store'])->name('media.store');
Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
