<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix(config('acme.starter.admin.prefix', 'admin'))->group(function (): void {
    Route::view('/users', 'acme-auth::admin.users.index')->name('acme.auth.users.index');
    Route::view('/sessions', 'acme-auth::admin.sessions.index')->name('acme.auth.sessions.index');
});
