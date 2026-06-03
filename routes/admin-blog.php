<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;

Route::resource('posts', PostController::class)->except('show')->scoped(['post' => 'id']);
Route::resource('categories', CategoryController::class)->except('show')->scoped(['category' => 'id']);

Route::get('tags', [TagController::class, 'index'])->name('tags.index');
Route::post('tags', [TagController::class, 'store'])->name('tags.store');
Route::put('tags/{tag:id}', [TagController::class, 'update'])->name('tags.update');
Route::delete('tags/{tag:id}', [TagController::class, 'destroy'])->name('tags.destroy');
