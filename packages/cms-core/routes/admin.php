<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/cms')
    ->group(function (): void {
        Route::view('/pages',  'acme-cms-core::admin.pages.index')->name('acme.cms.admin.pages.index');
        Route::view('/themes', 'acme-cms-core::admin.themes.index')->name('acme.cms.admin.themes.index');
    });
