<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/cart')
    ->name('acme.cart.admin.')
    ->group(function (): void {
        Route::view('/coupons', 'acme-cart::admin.coupons.index')->name('coupons.index');
        Route::view('/carts',   'acme-cart::admin.carts.index')->name('carts.index');
    });
