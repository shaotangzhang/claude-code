<?php

declare(strict_types=1);

namespace Acme\Membership\Listeners;

use Acme\Membership\Events\PaymentReceived;
use Acme\Membership\Models\Subscription;
use Acme\Membership\Services\SubscriptionService;

/**
 * When a downstream billing package dispatches PaymentReceived, we advance
 * the subscription. Listener is registered by MembershipServiceProvider.
 */
final class HandlePaymentReceived
{
    public function __construct(private readonly SubscriptionService $svc) {}

    public function handle(PaymentReceived $event): void
    {
        $sub = Subscription::query()->find($event->subscriptionId);
        if (! $sub) {
            return;
        }
        $this->svc->recordPayment($sub);
    }
}
