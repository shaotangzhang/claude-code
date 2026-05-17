<?php

declare(strict_types=1);

namespace Acme\Cart\Events;

final readonly class CartMerged
{
    public function __construct(
        public string $resultingCartId,
        public ?string $mergedFromGuestCartId,
        public string $userId,
    ) {}
}
