<?php

declare(strict_types=1);

namespace Acme\Rbac\Registry;

use Acme\Contracts\Module\CapabilityRegistry;

final class InMemoryCapabilityRegistry implements CapabilityRegistry
{
    /** @var array<string, array{key:string,label:string,group:?string}> */
    private array $items = [];

    public function register(string $key, string $label, ?string $group = null): void
    {
        $this->items[$key] = ['key' => $key, 'label' => $label, 'group' => $group];
    }

    public function registerMany(array $items): void
    {
        foreach ($items as $key => $entry) {
            if (is_string($entry)) {
                $this->register($key, $entry);

                continue;
            }
            if (is_array($entry)) {
                $this->register($key, (string) ($entry['label'] ?? $key), $entry['group'] ?? null);
            }
        }
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }
}
