<?php

declare(strict_types=1);

use Acme\Blog\Http\Controllers\ArticleController;
use Acme\Blog\Http\Controllers\CommentController;
use Acme\Blog\Http\Controllers\RssController;
use Acme\Blog\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix(config('acme.blog.route_prefix', 'blog'))->name('acme.blog.')->group(function (): void {
    Route::get('/feed.xml', RssController::class)->name('rss');

    Route::post('/subscribe',                 [SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::get('/subscribe/confirm/{token}',  [SubscriptionController::class, 'confirm'])->name('subscribe.confirm');
    Route::get('/unsubscribe/{token}',        [SubscriptionController::class, 'unsubscribe'])->name('unsubscribe');

    Route::post('/{slug}/comments',           [CommentController::class, 'store'])->name('comments.store');

    // Article detail must be the last route — catches anything not matched above.
    Route::get('/{slug}', [ArticleController::class, 'show'])->name('articles.show');
});
