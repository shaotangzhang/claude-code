<?php

declare(strict_types=1);

namespace Acme\CmsCore\Registry;

use Acme\Contracts\Cms\BlockRegistry;
use Acme\Contracts\Cms\BlockType;
use RuntimeException;

final class InMemoryBlockRegistry implements BlockRegistry
{
    /** @var array<string,class-string<BlockType>> */
    private array $types = [];

    public function register(string $blockType): void
    {
        if (! is_subclass_of($blockType, BlockType::class)) {
            throw new RuntimeException("{$blockType} must implement " . BlockType::class);
        }
        $this->types[$blockType::key()] = $blockType;
    }

    public function resolve(string $key): BlockType
    {
        $class = $this->types[$key] ?? throw new RuntimeException("Unknown block type: {$key}");

        return new $class();
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    public function all(): array
    {
        return array_values($this->types);
    }
}
