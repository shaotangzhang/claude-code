<?php

declare(strict_types=1);

namespace Acme\Commerce\Services;

use Acme\Checkout\Models\Order;
use Acme\Commerce\Events\ReturnRequested;
use Acme\Commerce\Models\ReturnItem;
use Acme\Commerce\Models\ReturnRequest;
use Acme\Payments\Models\Transaction;
use Acme\Payments\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ReturnService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @param  list<array{order_item_id:string,quantity:int,condition?:string,reason?:string}>  $items
     */
    public function request(Order $order, ?string $userId, array $items, ?string $reason = null): ReturnRequest
    {
        if (! $order->status->isPaid()) {
            throw new RuntimeException("Cannot return an unpaid order.");
        }
        $window = (int) config('acme.commerce.returns.window_days', 30);
        if ($order->paid_at && $order->paid_at->copy()->addDays($window)->isPast()) {
            throw new RuntimeException("Return window of {$window} days has elapsed.");
        }

        return DB::transaction(function () use ($order, $userId, $items, $reason): ReturnRequest {
            $rma = ReturnRequest::create([
                'number'       => 'RMA-' . strtoupper(substr((string) Str::ulid(), -8)),
                'order_id'     => $order->id,
                'user_id'      => $userId,
                'status'       => ReturnRequest::STATUS_REQUESTED,
                'reason'       => $reason,
                'requested_at' => CarbonImmutable::now(),
            ]);

            foreach ($items as $i) {
                ReturnItem::create([
                    'return_id'     => $rma->id,
                    'order_item_id' => $i['order_item_id'],
                    'quantity'      => (int) $i['quantity'],
                    'condition'     => $i['condition'] ?? null,
                    'reason'        => $i['reason']    ?? null,
                ]);
            }

            $this->events->dispatch(new ReturnRequested($rma->id, $rma->number, $order->id, $userId));

            return $rma;
        });
    }

    public function approve(ReturnRequest $rma): void
    {
        $this->guard($rma, ReturnRequest::STATUS_REQUESTED);
        $rma->status      = ReturnRequest::STATUS_APPROVED;
        $rma->approved_at = CarbonImmutable::now();
        $rma->save();
    }

    public function markReceived(ReturnRequest $rma): void
    {
        $this->guard($rma, ReturnRequest::STATUS_APPROVED);
        $rma->status      = ReturnRequest::STATUS_RECEIVED;
        $rma->received_at = CarbonImmutable::now();
        $rma->save();
    }

    public function reject(ReturnRequest $rma, ?string $reason = null): void
    {
        $this->guard($rma, ReturnRequest::STATUS_REQUESTED);
        $rma->status = ReturnRequest::STATUS_REJECTED;
        if ($reason) {
            $rma->reason = trim(($rma->reason ?? '') . "\n[rejected] {$reason}");
        }
        $rma->save();
    }

    /**
     * Refund the original payment transaction (whole or part) and close
     * the RMA. The actual gateway call is delegated to PaymentService.
     */
    public function refund(ReturnRequest $rma, int $amountCents): void
    {
        $this->guard($rma, ReturnRequest::STATUS_RECEIVED);

        $tx = Transaction::query()->where('related_type', 'order')
            ->where('related_id', $rma->order_id)
            ->where('status', Transaction::STATUS_SUCCEEDED)
            ->latest()->first();

        if (! $tx) {
            throw new RuntimeException("No succeeded payment to refund against.");
        }

        $this->payments->refund($tx, $amountCents);

        $rma->status              = ReturnRequest::STATUS_REFUNDED;
        $rma->refund_amount_cents = $amountCents;
        $rma->refunded_at         = CarbonImmutable::now();
        $rma->save();
    }

    private function guard(ReturnRequest $rma, string $expected): void
    {
        if ($rma->status !== $expected) {
            throw new RuntimeException("Expected RMA status '{$expected}', got '{$rma->status}'.");
        }
    }
}
