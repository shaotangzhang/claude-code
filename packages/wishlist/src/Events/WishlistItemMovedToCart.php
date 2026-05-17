<?php

declare(strict_types=1);

namespace Acme\Wishlist\Events;

final readonly class WishlistItemMovedToCart
{
    public function __construct(
        public string $userId,
        public string $skuId,
        public int $quantity,
        public string $cartId,
    ) {}
}
