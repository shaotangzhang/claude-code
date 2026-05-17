<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Events;

final readonly class PageDraftCreated
{
    public function __construct(
        public string $pageId,
        public string $versionId,
        public ?string $authorId,
    ) {}
}
