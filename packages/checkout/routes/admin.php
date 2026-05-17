<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/orders')
    ->name('acme.checkout.admin.')
    ->group(function (): void {
        Route::view('/',         'acme-checkout::admin.orders.index')->name('orders.index');
        Route::view('/invoices', 'acme-checkout::admin.invoices.index')->name('invoices.index');
    });
