<?php

declare(strict_types=1);

use Acme\AbandonedCart\Http\Controllers\RecoveryController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix(config('acme.abandoned-cart.route_prefix', 'cart'))
    ->name('acme.abandoned-cart.')
    ->group(function (): void {
        Route::get('/recover/{token}', [RecoveryController::class, 'show'])
            ->where('token', '[A-Za-z0-9]+')
            ->name('recover');
    });
