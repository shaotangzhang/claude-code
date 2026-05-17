<?php

declare(strict_types=1);

namespace Acme\Cart\Events;

final readonly class ItemAdded
{
    public function __construct(
        public string $cartId,
        public string $itemId,
        public string $skuId,
        public int $quantity,
    ) {}
}
