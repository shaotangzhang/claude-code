<?php

declare(strict_types=1);

use Acme\CmsAdmin\Http\Controllers\MenuController;
use Acme\CmsAdmin\Http\Controllers\PageController;
use Acme\CmsAdmin\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/cms')
    ->name('acme.cms.admin.')
    ->group(function (): void {
        Route::get('pages',  [PageController::class, 'index'])->name('pages.index');
        Route::get('pages/{page}/edit',  [PageController::class, 'edit'])->name('pages.edit');
        Route::put('versions/{version}', [PageController::class, 'saveDraft'])->name('versions.save');
        Route::post('pages/{page}/publish/{version}',  [PageController::class, 'publish'])->name('pages.publish');
        Route::post('pages/{page}/rollback/{version}', [PageController::class, 'rollback'])->name('pages.rollback');

        Route::get('themes',  [ThemeController::class, 'index'])->name('themes.index');
        Route::post('themes/{theme}/activate', [ThemeController::class, 'activate'])->name('themes.activate');

        Route::get('menus',         [MenuController::class, 'index'])->name('menus.index');
        Route::put('menus/{menu}',  [MenuController::class, 'update'])->name('menus.update');
    });
