<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;

// Posts.
Route::middleware('can:posts.view')->group(function () {
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
});
Route::middleware('can:posts.create')->group(function () {
    Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
});
Route::middleware('can:posts.update')->group(function () {
    Route::get('posts/{post:id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post:id}', [PostController::class, 'update'])->name('posts.update');
});
Route::middleware('can:posts.delete')->group(function () {
    Route::delete('posts/{post:id}', [PostController::class, 'destroy'])->name('posts.destroy');
});

// Categories.
Route::middleware('can:categories.view')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
});
Route::middleware('can:categories.create')->group(function () {
    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
});
Route::middleware('can:categories.update')->group(function () {
    Route::get('categories/{category:id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('categories/{category:id}', [CategoryController::class, 'update'])->name('categories.update');
});
Route::middleware('can:categories.delete')->group(function () {
    Route::delete('categories/{category:id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

// Tags (inline edit — no create/edit pages).
Route::middleware('can:tags.view')->group(function () {
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
});
Route::middleware('can:tags.create')->group(function () {
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
});
Route::middleware('can:tags.update')->group(function () {
    Route::put('tags/{tag:id}', [TagController::class, 'update'])->name('tags.update');
});
Route::middleware('can:tags.delete')->group(function () {
    Route::delete('tags/{tag:id}', [TagController::class, 'destroy'])->name('tags.destroy');
});
