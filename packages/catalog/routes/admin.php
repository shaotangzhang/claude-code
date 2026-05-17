<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/catalog')
    ->name('acme.catalog.admin.')
    ->group(function (): void {
        Route::view('/products',    'acme-catalog::admin.products.index')->name('products.index');
        Route::view('/categories',  'acme-catalog::admin.categories.index')->name('categories.index');
        Route::view('/brands',      'acme-catalog::admin.brands.index')->name('brands.index');
    });
