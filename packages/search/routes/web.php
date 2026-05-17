<?php

declare(strict_types=1);

use Acme\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix(config('acme.search.route_prefix', 'search'))->name('acme.search.')->group(function (): void {
    Route::get('/', [SearchController::class, 'show'])->name('show');
});
