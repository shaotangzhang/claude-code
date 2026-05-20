<?php

declare(strict_types=1);

use Acme\ReturnsPortal\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('account/returns')
    ->name('acme.returns-portal.')
    ->group(function (): void {
        Route::get('/',                       [PortalController::class, 'index'])->name('index');
        Route::get('/create/{orderId}',       [PortalController::class, 'create'])->name('create');
        Route::post('/create/{orderId}',      [PortalController::class, 'store'])->name('store');
        Route::get('/{return}',               [PortalController::class, 'show'])->name('show');
    });
