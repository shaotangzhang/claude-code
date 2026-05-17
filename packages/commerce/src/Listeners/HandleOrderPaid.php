<?php

declare(strict_types=1);

namespace Acme\Commerce\Listeners;

use Acme\Checkout\Events\OrderPaid;
use Acme\Checkout\Models\Order;
use Acme\Commerce\Services\LoyaltyService;
use Acme\Contracts\Commerce\StockAllocator;

/**
 * Reserve stock + award loyalty points when an order is paid.
 *
 * Idempotent: stock reservations are keyed off the order id, so a duplicate
 * OrderPaid (rare but possible with webhook retries) will fail in
 * StockService::reserveForOrder and be logged rather than re-reserving.
 */
final class HandleOrderPaid
{
    public function __construct(
        private readonly StockAllocator $stock,
        private readonly LoyaltyService $loyalty,
    ) {}

    public function handle(OrderPaid $event): void
    {
        $order = Order::with('items')->find($event->orderId);
        if (! $order) {
            return;
        }

        if (config('acme.commerce.inventory.auto_reserve_on_paid')) {
            $lines = $order->items
                ->filter(fn ($i) => $i->sku_id !== null)
                ->groupBy('sku_id')
                ->map(fn ($g) => (int) $g->sum('quantity'))
                ->all();

            try {
                $this->stock->reserveForOrder($order->id, $lines);
            } catch (\Throwable $e) {
                // Stock not available — keep order paid; ops will resolve.
                report($e);
            }
        }

        if (config('acme.commerce.loyalty.enabled') && $order->user_id) {
            $rate   = (float) config('acme.commerce.loyalty.points_per_cent', 0.01);
            $points = (int) floor($order->total_cents * $rate);
            if ($points > 0) {
                $this->loyalty->award(
                    userId:        $order->user_id,
                    points:        $points,
                    refType:       'order',
                    refId:         $order->id,
                    reason:        "Order {$order->number}",
                );
            }
        }
    }
}
