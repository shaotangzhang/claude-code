<?php

declare(strict_types=1);

namespace Acme\CmsCore\Registry;

use Acme\Contracts\Cms\ThemeManifest;
use Acme\Contracts\Cms\ThemeRegistry;
use RuntimeException;

final class InMemoryThemeRegistry implements ThemeRegistry
{
    /** @var array<string,ThemeManifest> */
    private array $items = [];

    private ?string $activeKey = null;

    public function register(ThemeManifest $theme): void
    {
        $this->items[$theme->key] = $theme;
    }

    public function resolve(string $key): ThemeManifest
    {
        return $this->items[$key] ?? throw new RuntimeException("Unknown theme: {$key}");
    }

    public function has(string $key): bool { return isset($this->items[$key]); }

    public function all(): array { return array_values($this->items); }

    public function active(): ?ThemeManifest
    {
        return $this->activeKey ? ($this->items[$this->activeKey] ?? null) : null;
    }

    public function activate(string $key): void
    {
        if (! $this->has($key)) {
            throw new RuntimeException("Cannot activate unknown theme: {$key}");
        }
        $this->activeKey = $key;
    }
}
