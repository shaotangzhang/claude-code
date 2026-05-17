<?php

declare(strict_types=1);

use Acme\Cart\Http\Controllers\CartController;
use Acme\Cart\Http\Controllers\CouponController;
use Acme\Cart\Http\Middleware\CartIdentifier;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', CartIdentifier::class])
    ->prefix(config('acme.cart.route_prefix', 'cart'))
    ->name('acme.cart.')
    ->group(function (): void {
        Route::get('/',                          [CartController::class, 'show'])->name('show');
        Route::post('/items',                    [CartController::class, 'addItem'])->name('items.add');
        Route::put('/items/{item}',              [CartController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}',           [CartController::class, 'removeItem'])->name('items.remove');
        Route::post('/coupons',                  [CouponController::class, 'apply'])->name('coupons.apply');
        Route::delete('/coupons/{coupon}',       [CouponController::class, 'remove'])->name('coupons.remove');
    });
