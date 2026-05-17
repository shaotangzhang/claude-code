<?php

declare(strict_types=1);

use Acme\CmsCore\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

if (config('acme.cms-core.routing.mount_catch_all', true)) {
    Route::middleware('web')
        ->get('/{slug?}', [PageController::class, 'show'])
        ->where('slug', '.*')
        ->name('acme.cms.page.show');
}
