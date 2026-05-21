<?php

declare(strict_types=1);

use Acme\Contracts\Module\ModuleRegistry;
use Illuminate\Support\Facades\Route;

/*
 * Demo home page — lists the installed acme/* modules. The catch-all
 * CMS route registered by acme/cms-core will eventually take over
 * "/" once a CMS Page is published at the root slug.
 */
Route::get('/_modules', function (ModuleRegistry $registry) {
    $modules = collect($registry->all())
        ->sortBy(fn ($m) => [$m->layer, $m->key])
        ->values();

    return response()->view('demo.modules', compact('modules'));
})->name('demo.modules');

Route::view('/_welcome', 'demo.welcome')->name('demo.welcome');
