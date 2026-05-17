<?php

declare(strict_types=1);

namespace Acme\Payments\Events;

final readonly class PaymentRefunded
{
    public function __construct(
        public string $transactionId,
        public string $gatewayKey,
        public string $relatedType,
        public string $relatedId,
        public int $amountCents,
        public string $currency,
    ) {}
}
