<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Events;

final readonly class ThemeActivated
{
    public function __construct(
        public string $themeKey,
        public ?string $previousThemeKey,
        public ?string $authorId,
    ) {}
}
