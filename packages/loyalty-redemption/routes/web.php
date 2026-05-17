<?php

declare(strict_types=1);

use Acme\Cart\Http\Middleware\CartIdentifier;
use Acme\LoyaltyRedemption\Http\Controllers\RedemptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', CartIdentifier::class])
    ->prefix(config('acme.cart.route_prefix', 'cart') . '/loyalty')
    ->name('acme.cart.loyalty.')
    ->group(function (): void {
        Route::post('/apply', [RedemptionController::class, 'apply'])->name('apply');
        Route::delete('/',    [RedemptionController::class, 'clear'])->name('clear');
    });
