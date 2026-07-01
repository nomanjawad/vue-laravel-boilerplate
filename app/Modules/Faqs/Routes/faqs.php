<?php

// Routes for the Faq resource (Faqs module).
// Loaded via AbstractModuleServiceProvider::bootModule() → loadRoutesFrom(),
// which is a bare `require` with NO outer group — so this file has to apply
// its own web/auth/admin middleware. Do not remove that group without
// also updating the abstract provider.

use App\Modules\Faqs\Http\Controllers\Admin\FaqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin', 'module:faqs'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('faqs', [FaqController::class, 'index'])
            ->middleware('can:faqs.view')
            ->name('faqs.index');

        Route::get('faqs/create', [FaqController::class, 'create'])
            ->middleware('can:faqs.create')
            ->name('faqs.create');

        Route::post('faqs', [FaqController::class, 'store'])
            ->middleware('can:faqs.create')
            ->name('faqs.store');

        // Admin routes bind by id (Laravel's default). Public routes that you
        // wire by hand should use {faq:slug} for slug binding.
        Route::get('faqs/{faq}/edit', [FaqController::class, 'edit'])
            ->middleware('can:faqs.update')
            ->name('faqs.edit');

        Route::put('faqs/{faq}', [FaqController::class, 'update'])
            ->middleware('can:faqs.update')
            ->name('faqs.update');

        Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])
            ->middleware('can:faqs.delete')
            ->name('faqs.destroy');
    });
