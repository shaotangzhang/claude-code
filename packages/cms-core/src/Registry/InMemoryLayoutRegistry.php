<?php

declare(strict_types=1);

namespace Acme\CmsCore\Registry;

use Acme\Contracts\Cms\LayoutDefinition;
use Acme\Contracts\Cms\LayoutRegistry;
use RuntimeException;

final class InMemoryLayoutRegistry implements LayoutRegistry
{
    /** @var array<string,LayoutDefinition> */
    private array $items = [];

    public function register(LayoutDefinition $layout): void
    {
        $this->items[$layout->key] = $layout;
    }

    public function resolve(string $key): LayoutDefinition
    {
        return $this->items[$key] ?? throw new RuntimeException("Unknown layout: {$key}");
    }

    public function has(string $key): bool { return isset($this->items[$key]); }

    public function all(): array { return array_values($this->items); }
}
