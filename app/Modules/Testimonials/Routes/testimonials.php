<?php

// Routes for the Testimonial resource (Testimonials module).
// Loaded via AbstractModuleServiceProvider::bootModule() → loadRoutesFrom(),
// which is a bare `require` with NO outer group — so this file has to apply
// its own web/auth/admin middleware. Do not remove that group without
// also updating the abstract provider.

use App\Modules\Testimonials\Http\Controllers\Admin\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin', 'module:testimonials'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('testimonials', [TestimonialController::class, 'index'])
            ->middleware('can:testimonials.view')
            ->name('testimonials.index');

        Route::get('testimonials/create', [TestimonialController::class, 'create'])
            ->middleware('can:testimonials.create')
            ->name('testimonials.create');

        Route::post('testimonials', [TestimonialController::class, 'store'])
            ->middleware('can:testimonials.create')
            ->name('testimonials.store');

        // Admin routes bind by id (Laravel's default). Public routes that you
        // wire by hand should use {testimonial:slug} for slug binding.
        Route::get('testimonials/{testimonial}/edit', [TestimonialController::class, 'edit'])
            ->middleware('can:testimonials.update')
            ->name('testimonials.edit');

        Route::put('testimonials/{testimonial}', [TestimonialController::class, 'update'])
            ->middleware('can:testimonials.update')
            ->name('testimonials.update');

        Route::delete('testimonials/{testimonial}', [TestimonialController::class, 'destroy'])
            ->middleware('can:testimonials.delete')
            ->name('testimonials.destroy');
    });
