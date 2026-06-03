<?php

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::resource('products', ProductController::class)->except('show');

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
