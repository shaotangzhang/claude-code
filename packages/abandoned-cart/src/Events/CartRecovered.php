<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Events;

final readonly class CartRecovered
{
    public function __construct(
        public string $cartId,
        public ?string $userId,
    ) {}
}
