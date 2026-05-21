<?php

declare(strict_types=1);

use Acme\CmsCore\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

if (config('acme.cms-core.routing.mount_catch_all', true)) {
    // Use fallback so explicit routes in the host app (e.g. /_welcome,
    // /_modules) or other packages always win. CMS pages are looked up
    // only when nothing else matched.
    Route::middleware('web')
        ->fallback([PageController::class, 'show'])
        ->name('acme.cms.page.show');
}
