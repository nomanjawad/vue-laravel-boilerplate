<?php

use App\Http\Controllers\Public\BlogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['module:blog', 'responsecache'])->group(function () {
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    // Must register before /blog/{post:slug} so "category" is not captured as a post slug.
    Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
});
