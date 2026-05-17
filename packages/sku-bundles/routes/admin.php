<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/bundles')
    ->name('acme.bundles.admin.')
    ->group(function (): void {
        Route::view('/', 'acme-sku-bundles::admin.index')->name('index');
    });
