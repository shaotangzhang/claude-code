<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix(config('acme.starter.admin.prefix', 'admin'))->group(function (): void {
    Route::view('/roles', 'acme-rbac::admin.roles.index')->name('acme.rbac.roles.index');
});
