<?php

// Routes for the Event resource (Events module).
// Loaded via AbstractModuleServiceProvider::bootModule() → loadRoutesFrom(),
// which is a bare `require` with NO outer group — so this file has to apply
// its own web/auth/admin middleware. Do not remove that group without
// also updating the abstract provider.

use App\Modules\Events\Http\Controllers\Admin\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin', 'module:events'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('events', [EventController::class, 'index'])
            ->middleware('can:events.view')
            ->name('events.index');

        Route::get('events/create', [EventController::class, 'create'])
            ->middleware('can:events.create')
            ->name('events.create');

        Route::post('events', [EventController::class, 'store'])
            ->middleware('can:events.create')
            ->name('events.store');

        // Admin routes bind by id (Laravel's default). Public routes that you
        // wire by hand should use {event:slug} for slug binding.
        Route::get('events/{event}/edit', [EventController::class, 'edit'])
            ->middleware('can:events.update')
            ->name('events.edit');

        Route::put('events/{event}', [EventController::class, 'update'])
            ->middleware('can:events.update')
            ->name('events.update');

        Route::delete('events/{event}', [EventController::class, 'destroy'])
            ->middleware('can:events.delete')
            ->name('events.destroy');
    });
