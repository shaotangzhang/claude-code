<?php

declare(strict_types=1);

namespace Acme\Admin\Registry;

use Acme\Contracts\Auth\UserResolver;
use Acme\Contracts\Module\NavigationItem;
use Acme\Contracts\Module\NavigationRegistry;

final class InMemoryNavigationRegistry implements NavigationRegistry
{
    /** @var list<NavigationItem> */
    private array $items = [];

    public function __construct(private readonly ?UserResolver $users = null) {}

    public function register(NavigationItem $item): void
    {
        $this->items[] = $item;
    }

    public function registerMany(array $items): void
    {
        foreach ($items as $item) {
            $this->register($item);
        }
    }

    public function for(string $area): array
    {
        $filtered = array_values(array_filter(
            $this->items,
            fn (NavigationItem $i): bool => $i->area === $area && $this->visible($i),
        ));

        usort($filtered, fn ($a, $b) => [$a->group ?? '', $a->order, $a->label]
            <=> [$b->group ?? '', $b->order, $b->label]);

        return $filtered;
    }

    private function visible(NavigationItem $item): bool
    {
        if ($item->capability === null) {
            return true;
        }
        if ($this->users === null) {
            return true; // no resolver bound → don't hide
        }

        return $this->users->currentUserCan($item->capability);
    }
}
