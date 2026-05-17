<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Events;

final readonly class PagePublished
{
    public function __construct(
        public string $pageId,
        public string $versionId,
        public ?string $authorId,
        public bool $scheduled = false,
        public ?string $publishAt = null,
    ) {}
}
