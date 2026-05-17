<?php

declare(strict_types=1);

use Acme\Cart\Http\Middleware\CartIdentifier;
use Acme\SkuBundles\Http\Controllers\BundleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', CartIdentifier::class])
    ->prefix(config('acme.cart.route_prefix', 'cart') . '/bundles')
    ->name('acme.cart.bundles.')
    ->group(function (): void {
        Route::post('/',                  [BundleController::class, 'add'])->name('add');
        Route::delete('/{sourceKey}',     [BundleController::class, 'remove'])->name('remove')
            ->where('sourceKey', '[A-Za-z0-9\-:]+');
    });
