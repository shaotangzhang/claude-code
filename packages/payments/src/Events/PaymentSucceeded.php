<?php

declare(strict_types=1);

namespace Acme\Payments\Events;

/**
 * The universal "money's in" event. Consumers identify the affected
 * resource via $relatedType + $relatedId (e.g. order, subscription).
 *
 * Domains depending on acme/payments listen to this and update their
 * own state. Listener implementations must be idempotent — duplicate
 * webhooks are normal.
 */
final readonly class PaymentSucceeded
{
    public function __construct(
        public string $transactionId,
        public string $gatewayKey,
        public ?string $gatewayReference,
        public string $relatedType,
        public string $relatedId,
        public int $amountCents,
        public string $currency,
        public string $succeededAtIso,
    ) {}
}
