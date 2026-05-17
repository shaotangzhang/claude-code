<?php

declare(strict_types=1);

namespace Acme\Contracts\Payments;

/** Describes a charge to make. Producer fills this; gateway returns a PaymentResult. */
final readonly class PaymentIntent
{
    public function __construct(
        public string $transactionId,   // acme_payments_transactions.id we already wrote
        public int $amountCents,
        public string $currency,
        public string $relatedType,     // e.g. "order", "subscription"
        public string $relatedId,
        public ?string $returnUrl = null,
        public ?string $description = null,
        public array $metadata = [],
    ) {}
}
