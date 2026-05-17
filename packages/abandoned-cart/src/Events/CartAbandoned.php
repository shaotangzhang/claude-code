<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Events;

final readonly class CartAbandoned
{
    public function __construct(
        public string $cartId,
        public ?string $userId,
        public ?string $email,
        public string $recoveryToken,
        public string $recoveryUrl,
        public int $itemCount,
        public int $totalCents,
        public string $currency,
    ) {}
}
