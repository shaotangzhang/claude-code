<?php

declare(strict_types=1);

use Acme\Seo\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('/sitemap.xml', SitemapController::class)
    ->name('acme.seo.sitemap');

Route::middleware('web')->get('/robots.txt', function () {
    $body = "User-agent: *\nAllow: /\nSitemap: " . url('/sitemap.xml') . "\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('acme.seo.robots');
