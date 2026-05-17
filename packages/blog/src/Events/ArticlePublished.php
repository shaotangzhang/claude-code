<?php

declare(strict_types=1);

namespace Acme\Blog\Events;

final readonly class ArticlePublished
{
    public function __construct(
        public string $articleId,
        public string $slug,
        public string $locale,
        public ?string $authorId,
    ) {}
}
