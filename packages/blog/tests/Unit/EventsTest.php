<?php

declare(strict_types=1);

namespace Acme\Blog\Tests\Unit;

use Acme\Blog\Events\ArticlePublished;
use Acme\Blog\Events\CommentReceived;
use Acme\Blog\Events\SubscriberConfirmed;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_events_are_immutable(): void
    {
        $a = new ArticlePublished('art1', 'hello', 'en', 'u1');
        $this->assertSame('art1', $a->articleId);
        $this->assertSame('hello', $a->slug);

        $c = new CommentReceived('c1', 'art1', 'pending');
        $this->assertSame('pending', $c->status);

        $s = new SubscriberConfirmed('s1', 'a@b.c', 'en');
        $this->assertSame('a@b.c', $s->email);
    }
}
