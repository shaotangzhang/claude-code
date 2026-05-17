<?php

declare(strict_types=1);

namespace Acme\Contracts\Module;

final readonly class NavigationItem
{
    /**
     * @param  list<NavigationItem>  $children
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $area,          // e.g. "admin" | "user-center"
        public ?string $route = null, // named route
        public ?string $url = null,   // raw URL (used if route is null)
        public ?string $icon = null,
        public ?string $capability = null,
        public ?string $group = null,
        public int $order = 100,
        public array $children = [],
    ) {}
}
