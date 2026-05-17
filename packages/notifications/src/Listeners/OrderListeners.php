<?php

declare(strict_types=1);

namespace Acme\Notifications\Listeners;

use Acme\Checkout\Events\OrderCanceled;
use Acme\Checkout\Events\OrderFulfilled;
use Acme\Checkout\Events\OrderPaid;
use Acme\Checkout\Events\OrderPlaced;
use Acme\Notifications\Dispatcher;

final class OrderListeners
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function onPlaced(OrderPlaced $e): void
    {
        $this->dispatcher->dispatch('order.placed', [
            'user_id'   => $e->userId,
            'subject'   => "Order {$e->number} placed",
            'body_text' => "Thanks — order {$e->number} totalling {$e->currency} " . number_format($e->totalCents / 100, 2) . " is awaiting payment.",
        ]);
    }

    public function onPaid(OrderPaid $e): void
    {
        $this->dispatcher->dispatch('order.paid', [
            'user_id'   => $e->userId,
            'subject'   => "Order {$e->number} paid",
            'body_text' => "Payment received for order {$e->number}.",
        ]);
    }

    public function onFulfilled(OrderFulfilled $e): void
    {
        $this->dispatcher->dispatch('order.fulfilled', [
            'user_id'   => $e->userId,
            'subject'   => "Order {$e->number} shipped",
            'body_text' => "Your order {$e->number} has been fulfilled.",
        ]);
    }

    public function onCanceled(OrderCanceled $e): void
    {
        $this->dispatcher->dispatch('order.canceled', [
            'user_id'   => $e->userId,
            'subject'   => "Order {$e->number} canceled",
            'body_text' => "Order {$e->number} has been canceled" . ($e->reason ? " ({$e->reason})" : '') . ".",
        ]);
    }
}
