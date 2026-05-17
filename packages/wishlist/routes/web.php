<?php

declare(strict_types=1);

use Acme\Cart\Http\Middleware\CartIdentifier;
use Acme\Wishlist\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.wishlist.route_prefix', 'wishlist'))
    ->name('acme.wishlist.')
    ->group(function (): void {
        Route::get('/',                       [WishlistController::class, 'show'])->name('show');
        Route::post('/items',                 [WishlistController::class, 'addItem'])->name('items.add');
        Route::delete('/items/{item}',        [WishlistController::class, 'removeItem'])->name('items.remove');
        Route::post('/items/{item}/to-cart',  [WishlistController::class, 'moveToCart'])
            ->middleware(CartIdentifier::class)
            ->name('items.to-cart');
    });
