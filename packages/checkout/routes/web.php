<?php

declare(strict_types=1);

use Acme\Checkout\Http\Controllers\CheckoutController;
use Acme\Checkout\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix(config('acme.checkout.route_prefix', 'checkout'))->name('acme.checkout.')->group(function (): void {
    Route::get('/',       [CheckoutController::class, 'show'])->name('show');
    Route::post('/place', [CheckoutController::class, 'place'])->name('place');
});

Route::middleware(['web', 'auth'])->prefix('orders')->name('acme.checkout.orders.')->group(function (): void {
    Route::get('/',         [OrderController::class, 'index'])->name('index');
    Route::get('/{order}',  [OrderController::class, 'show'])->name('show');
});
