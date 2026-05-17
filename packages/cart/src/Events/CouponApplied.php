<?php

declare(strict_types=1);

namespace Acme\Cart\Events;

final readonly class CouponApplied
{
    public function __construct(
        public string $cartId,
        public string $couponId,
        public string $code,
    ) {}
}
