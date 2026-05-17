<?php

declare(strict_types=1);

use Acme\Catalog\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix(config('acme.catalog.route_prefix', 'catalog'))
    ->name('acme.catalog.')
    ->group(function (): void {
        Route::get('/{slug}', [ProductController::class, 'show'])->name('products.show');
    });
