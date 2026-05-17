<?php

declare(strict_types=1);

namespace Acme\Checkout\Services;

use Acme\Checkout\Enums\OrderStatus;
use Acme\Checkout\Events\OrderCanceled;
use Acme\Checkout\Events\OrderFulfilled;
use Acme\Checkout\Events\OrderPaid;
use Acme\Checkout\Models\Invoice;
use Acme\Checkout\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class OrderService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function markPaid(Order $order, string $transactionId, CarbonImmutable $at): void
    {
        if ($order->status === OrderStatus::Paid || $order->status === OrderStatus::Fulfilled) {
            return; // idempotent
        }
        if ($order->status->isTerminal()) {
            throw new RuntimeException("Refusing to mark a terminal order paid: {$order->status->value}");
        }

        DB::transaction(function () use ($order, $transactionId, $at): void {
            $order->status                 = OrderStatus::Paid;
            $order->paid_at                = $at;
            $order->payment_transaction_id = $transactionId;
            $order->save();

            $inv = $order->invoice;
            if ($inv && $inv->status !== Invoice::STATUS_PAID) {
                $inv->status    = Invoice::STATUS_PAID;
                $inv->issued_at = $inv->issued_at ?? $at;
                $inv->paid_at   = $at;
                $inv->save();
            }
        });

        $this->events->dispatch(new OrderPaid(
            orderId:       $order->id,
            number:        $order->number,
            userId:        $order->user_id,
            transactionId: $transactionId,
            paidAtIso:     $at->toIso8601String(),
        ));
    }

    public function markFulfilled(Order $order): void
    {
        if (! $order->status->isPaid()) {
            throw new RuntimeException("Can only fulfil paid orders; current={$order->status->value}");
        }

        $order->status       = OrderStatus::Fulfilled;
        $order->fulfilled_at = CarbonImmutable::now();
        $order->save();

        $this->events->dispatch(new OrderFulfilled(
            orderId:        $order->id,
            number:         $order->number,
            userId:         $order->user_id,
            fulfilledAtIso: $order->fulfilled_at->toIso8601String(),
        ));
    }

    public function cancel(Order $order, ?string $reason = null): void
    {
        if ($order->status->isTerminal()) {
            return;
        }

        $order->status      = OrderStatus::Canceled;
        $order->canceled_at = CarbonImmutable::now();
        $order->save();

        $this->events->dispatch(new OrderCanceled(
            orderId: $order->id,
            number:  $order->number,
            userId:  $order->user_id,
            reason:  $reason,
        ));
    }
}
