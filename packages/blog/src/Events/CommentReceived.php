<?php

declare(strict_types=1);

namespace Acme\Blog\Events;

final readonly class CommentReceived
{
    public function __construct(
        public string $commentId,
        public string $articleId,
        public string $status,
    ) {}
}
