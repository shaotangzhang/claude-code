<?php

declare(strict_types=1);

namespace Acme\Wishlist\Events;

final readonly class WishlistItemAdded
{
    public function __construct(
        public string $listId,
        public string $itemId,
        public string $userId,
        public string $skuId,
    ) {}
}
