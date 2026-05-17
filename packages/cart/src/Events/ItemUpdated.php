<?php

declare(strict_types=1);

namespace Acme\Cart\Events;

final readonly class ItemUpdated
{
    public function __construct(
        public string $cartId,
        public string $itemId,
        public int $quantity,
    ) {}
}
