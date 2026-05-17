<?php

declare(strict_types=1);

namespace Acme\Contracts\Cms;

interface LayoutRegistry
{
    public function register(LayoutDefinition $layout): void;

    public function resolve(string $key): LayoutDefinition;

    public function has(string $key): bool;

    /** @return list<LayoutDefinition> */
    public function all(): array;
}
