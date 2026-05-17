<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

interface BlockRegistry
{
    /** @param class-string<BlockType> $blockType */
    public function register(string $blockType): void;

    public function resolve(string $key): BlockType;

    public function has(string $key): bool;

    /** @return list<class-string<BlockType>> */
    public function all(): array;
}
