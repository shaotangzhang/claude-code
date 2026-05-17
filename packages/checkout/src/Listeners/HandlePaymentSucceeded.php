<?php

declare(strict_types=1);

namespace Acme\Checkout\Listeners;

use Acme\Checkout\Models\Order;
use Acme\Checkout\Services\OrderService;
use Acme\Payments\Events\PaymentSucceeded;
use Carbon\CarbonImmutable;

/**
 * Bridges payments → orders. Listens to the universal PaymentSucceeded
 * event from acme/payments and, when relatedType=order, marks the
 * matching order paid. Idempotent.
 */
final class HandlePaymentSucceeded
{
    public function __construct(private readonly OrderService $orders) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->relatedType !== 'order') {
            return;
        }

        $order = Order::query()->find($event->relatedId);
        if (! $order) {
            return;
        }

        $this->orders->markPaid(
            $order,
            $event->transactionId,
            CarbonImmutable::parse($event->succeededAtIso),
        );
    }
}
