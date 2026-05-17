<?php

declare(strict_types=1);

use Acme\Payments\Http\Controllers\ManualController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/payments')
    ->name('acme.payments.admin.')
    ->group(function (): void {
        Route::view('/transactions', 'acme-payments::admin.transactions.index')->name('transactions.index');
        Route::post('/transactions/{transaction}/confirm', [ManualController::class, 'confirm'])->name('transactions.confirm');
        Route::post('/transactions/{transaction}/reject',  [ManualController::class, 'reject'])->name('transactions.reject');
    });
