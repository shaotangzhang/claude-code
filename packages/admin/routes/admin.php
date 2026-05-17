<?php

declare(strict_types=1);

use Acme\Admin\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.admin.prefix', 'admin'))
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('acme.admin.dashboard');
    });
