<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption\Listeners;

use Acme\Cart\Models\Cart;
use Acme\Checkout\Events\OrderPaid;
use Acme\Checkout\Models\Order;
use Acme\Commerce\Services\LoyaltyService;
use Acme\LoyaltyRedemption\Support\RedemptionState;

/**
 * When the converted-cart's order is paid, debit the points the user
 * had redeemed. We locate the originating cart by user + most-recent
 * converted cart (Order doesn't store cart_id by default).
 *
 * Idempotent: if redemption state already cleared, this is a no-op.
 */
final class HandleOrderPaid
{
    public function __construct(private readonly LoyaltyService $loyalty) {}

    public function handle(OrderPaid $event): void
    {
        if (! $event->userId) {
            return;
        }

        $order = Order::query()->find($event->orderId);
        if (! $order) {
            return;
        }

        // The cart is most-recent converted with same user_id around the
        // order's placed_at. Cheap heuristic: latest converted for user.
        $cart = Cart::query()
            ->where('user_id', $event->userId)
            ->where('status', Cart::STATUS_CONVERTED)
            ->orderByDesc('updated_at')
            ->first();
        if (! $cart) {
            return;
        }

        $state = RedemptionState::get($cart);
        if (! $state || $state['points'] <= 0) {
            return;
        }

        $this->loyalty->redeem(
            userId:  $event->userId,
            points:  $state['points'],
            refType: 'order',
            refId:   $order->id,
        );

        RedemptionState::clear($cart);
    }
}
