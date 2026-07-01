<?php

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:shop')->group(function () {
// Products.
Route::middleware('can:products.view')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
});
Route::middleware('can:products.create')->group(function () {
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
});
Route::middleware('can:products.update')->group(function () {
    Route::get('products/{product:id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product:id}', [ProductController::class, 'update'])->name('products.update');
});
Route::middleware('can:products.delete')->group(function () {
    Route::delete('products/{product:id}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// Orders (no create/destroy — driven by the public checkout).
Route::middleware('can:orders.view')->group(function () {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order:id}', [OrderController::class, 'show'])->name('orders.show');
});
Route::middleware('can:orders.update')->group(function () {
    Route::patch('orders/{order:id}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
});
});
