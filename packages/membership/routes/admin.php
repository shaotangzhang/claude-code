<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/membership')
    ->name('acme.membership.admin.')
    ->group(function (): void {
        Route::view('/plans',         'acme-membership::admin.plans.index')->name('plans.index');
        Route::view('/subscriptions', 'acme-membership::admin.subscriptions.index')->name('subscriptions.index');
    });
