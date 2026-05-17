<?php

declare(strict_types=1);

namespace Acme\Checkout\Events;

final readonly class OrderPaid
{
    public function __construct(
        public string $orderId,
        public string $number,
        public ?string $userId,
        public string $transactionId,
        public string $paidAtIso,
    ) {}
}
