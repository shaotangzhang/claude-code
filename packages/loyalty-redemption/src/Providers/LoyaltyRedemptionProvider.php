<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption\Providers;

use Acme\Cart\Models\Cart;
use Acme\Contracts\Commerce\CartAdjustment;
use Acme\Contracts\Commerce\CartAdjustmentProvider;
use Acme\LoyaltyRedemption\Support\RedemptionState;

/**
 * Surfaces the cart-level redemption stored on cart.meta_json as a
 * discount adjustment. Pure read — no DB writes. State is committed
 * (points debited) on OrderPaid via a separate listener.
 */
final class LoyaltyRedemptionProvider implements CartAdjustmentProvider
{
    public function adjustmentsFor(array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        if (! $userId) {
            return [];
        }

        // We need the Cart row to fetch meta_json. Look it up by user.
        $cart = Cart::query()->where('user_id', $userId)->where('status', Cart::STATUS_ACTIVE)->first();
        if (! $cart) {
            return [];
        }

        $state = RedemptionState::get($cart);
        if (! $state || $state['currency'] !== $currency) {
            return [];
        }

        $cap = min((int) $state['amount_cents'], $subtotalCents);
        if ($cap <= 0) {
            return [];
        }

        return [new CartAdjustment(
            sourceKey:   'loyalty:redeem',
            description: "Loyalty: {$state['points']} pts",
            amountCents: -$cap,
        )];
    }
}
