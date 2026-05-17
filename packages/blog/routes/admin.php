<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.starter.admin.prefix', 'admin') . '/blog')
    ->name('acme.blog.admin.')
    ->group(function (): void {
        Route::view('/articles',  'acme-blog::admin.articles.index')->name('articles.index');
        Route::view('/comments',  'acme-blog::admin.comments.index')->name('comments.index');
        Route::view('/subscribers', 'acme-blog::admin.subscribers.index')->name('subscribers.index');
    });
