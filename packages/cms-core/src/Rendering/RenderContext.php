<?php

declare(strict_types=1);

namespace Acme\CmsCore\Rendering;

use Acme\Contracts\Cms\RenderContext as RenderContextContract;

final class RenderContext implements RenderContextContract
{
    /** @var array<string,mixed> */
    private array $bag = [];

    public function __construct(
        private readonly string $locale,
        private readonly ?string $pageId = null,
        private readonly ?string $userId = null,
        private readonly ?string $themeKey = null,
    ) {}

    public function locale(): string { return $this->locale; }
    public function pageId(): ?string { return $this->pageId; }
    public function userId(): ?string { return $this->userId; }
    public function themeKey(): ?string { return $this->themeKey; }
    public function bag(): array { return $this->bag; }

    public function set(string $key, mixed $value): void { $this->bag[$key] = $value; }
    public function get(string $key, mixed $default = null): mixed { return $this->bag[$key] ?? $default; }
}
