<?php

use App\Http\Controllers\Public\CartController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{product:slug}', [ShopController::class, 'show'])->name('shop.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{productId}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/order/{orderNumber}/confirmation', [CheckoutController::class, 'confirmation'])->name('order.confirmation');
});
