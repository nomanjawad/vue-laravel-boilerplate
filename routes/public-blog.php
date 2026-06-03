<?php

use App\Http\Controllers\Public\BlogController;
use Illuminate\Support\Facades\Route;

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
