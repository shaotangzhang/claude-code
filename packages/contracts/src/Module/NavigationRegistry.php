<?php

declare(strict_types=1);

namespace Acme\Contracts\Module;

interface NavigationRegistry
{
    public function register(NavigationItem $item): void;

    /** @param list<NavigationItem> $items */
    public function registerMany(array $items): void;

    /** Return items for a given area, sorted, filtered by capability if a resolver is bound. */
    public function for(string $area): array;
}
