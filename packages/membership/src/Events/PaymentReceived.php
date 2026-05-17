<?php

declare(strict_types=1);

namespace Acme\Membership\Events;

/**
 * Listener of PaymentDue dispatches this back when the charge confirms.
 * SubscriptionService::recordPayment() is the canonical handler — it
 * advances current_period_end and transitions status to Active.
 */
final readonly class PaymentReceived
{
    public function __construct(
        public string $subscriptionId,
        public int $amountCents,
        public string $currency,
        public string $referenceId,   // gateway-side id (charge / intent / receipt)
        public string $receivedAtIso,
    ) {}
}
