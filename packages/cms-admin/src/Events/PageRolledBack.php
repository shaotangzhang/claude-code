<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Events;

final readonly class PageRolledBack
{
    public function __construct(
        public string $pageId,
        public string $fromVersionId,
        public string $toVersionId,
        public ?string $authorId,
    ) {}
}
