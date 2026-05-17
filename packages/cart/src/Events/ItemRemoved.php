<?php

declare(strict_types=1);

namespace Acme\Cart\Events;

final readonly class ItemRemoved
{
    public function __construct(
        public string $cartId,
        public string $itemId,
    ) {}
}
