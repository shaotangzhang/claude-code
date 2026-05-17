<?php

declare(strict_types=1);

namespace Acme\CmsCore\Registry;

use Acme\Contracts\Cms\WidgetRegistry;
use RuntimeException;

final class InMemoryWidgetRegistry implements WidgetRegistry
{
    /** @var array<string,class-string> */
    private array $items = [];

    public function register(string $key, string $widgetClass): void
    {
        $this->items[$key] = $widgetClass;
    }

    public function resolve(string $key): string
    {
        return $this->items[$key] ?? throw new RuntimeException("Unknown widget: {$key}");
    }

    public function has(string $key): bool { return isset($this->items[$key]); }

    public function all(): array { return $this->items; }
}
