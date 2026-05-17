<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/commerce')
    ->name('acme.commerce.admin.')
    ->group(function (): void {
        Route::view('/inventory', 'acme-commerce::admin.inventory.index')->name('inventory.index');
        Route::view('/returns',   'acme-commerce::admin.returns.index')->name('returns.index');
        Route::view('/reviews',   'acme-commerce::admin.reviews.index')->name('reviews.index');
        Route::view('/campaigns', 'acme-commerce::admin.campaigns.index')->name('campaigns.index');
        Route::view('/loyalty',   'acme-commerce::admin.loyalty.index')->name('loyalty.index');
    });
