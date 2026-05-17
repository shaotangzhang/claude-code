<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.user-center.prefix', 'account'))
    ->group(function (): void {
        Route::view('/',         'acme-user-center::profile')->name('acme.account.profile');
        Route::view('/security', 'acme-user-center::security')->name('acme.account.security');
        Route::view('/sessions', 'acme-user-center::sessions')->name('acme.account.sessions');
    });
