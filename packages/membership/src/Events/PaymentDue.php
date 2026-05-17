<?php

declare(strict_types=1);

namespace Acme\Membership\Events;

/**
 * THE payment-gateway boundary event.
 *
 * Membership emits this when a recurring subscription's period ends OR
 * when a trial converts to paid OR when starting a non-zero-priced plan.
 * A downstream billing package (acme/checkout, acme/payments-stripe, or
 * a custom one) must listen and either:
 *   - dispatch PaymentReceived back when the charge succeeds, OR
 *   - leave it; the tick command will move the subscription to past_due,
 *     then to expired after the grace period.
 *
 * Listener is responsible for idempotency (use subscriptionId + dueIso).
 */
final readonly class PaymentDue
{
    public function __construct(
        public string $subscriptionId,
        public string $userId,
        public string $planKey,
        public int $amountCents,
        public string $currency,
        public string $dueIso,
        public bool $isInitial,    // true on first charge / post-trial; false on renewal
    ) {}
}
