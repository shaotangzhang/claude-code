<?php

declare(strict_types=1);

namespace Acme\Notifications\Listeners;

use Acme\Blog\Events\ArticlePublished;
use Acme\Blog\Models\Subscription;
use Acme\Notifications\Dispatcher;

final class BlogListeners
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function onArticlePublished(ArticlePublished $e): void
    {
        $subscribers = Subscription::query()
            ->whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->where('locale', $e->locale)
            ->pluck('email');

        foreach ($subscribers as $email) {
            $this->dispatcher->dispatch('article.published', [
                'recipient' => $email,
                'subject'   => "New post: {$e->slug}",
                'body_text' => "Read it at /blog/{$e->slug}",
            ]);
        }
    }
}
