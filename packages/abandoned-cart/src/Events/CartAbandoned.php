<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Events;

/**
 * Emitted each time a reminder round fires for an abandoned cart.
 *
 * round = 1  → initial mark + first email
 * round = 2  → 48h-after-mark nudge with coupon
 * round = 3  → 144h-after-mark final-chance with bigger coupon
 *
 * couponCode is the human-typeable code generated for this round, or
 * null if the round template had no coupon configured.
 *
 * Optional fields `round` and `couponCode` default to (1, null) to
 * preserve compatibility with 0.1 callers.
 */
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
        public int $round = 1,
        public ?string $couponCode = null,
    ) {}
}
