<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption\Services;

use Acme\Cart\Models\Cart;
use Acme\Cart\Services\TotalsCalculator;
use Acme\Commerce\Models\LoyaltyAccount;
use Acme\Commerce\Services\LoyaltyService;
use Acme\LoyaltyRedemption\Support\RedemptionState;
use RuntimeException;

/**
 * Sets / clears a redemption intent on a cart. Does NOT debit points —
 * that happens on OrderPaid (via HandleOrderPaid listener) so points
 * are only consumed when payment lands.
 */
final class LoyaltyRedemptionService
{
    public function __construct(
        private readonly LoyaltyService $loyalty,
        private readonly TotalsCalculator $totals,
    ) {}

    public function apply(Cart $cart, int $points): array
    {
        if (! $cart->user_id) {
            throw new RuntimeException("Loyalty redemption requires a logged-in cart.");
        }
        if ($points <= 0) {
            throw new RuntimeException("Points must be positive.");
        }

        $account = $this->loyalty->accountFor($cart->user_id);
        if ($account->balance < $points) {
            throw new RuntimeException("Insufficient balance: have {$account->balance}, want {$points}.");
        }

        $rate     = (int) config('acme.commerce.loyalty.redeem_cents_per_point', 1);
        $maxRedeem = (int) $cart->subtotal_cents;
        $amount    = min($points * $rate, $maxRedeem);
        if ($amount <= 0) {
            throw new RuntimeException("Cart subtotal too low to redeem.");
        }
        // Re-derive actual points used given the cap.
        $actualPoints = intdiv($amount, max(1, $rate));

        RedemptionState::set($cart, $actualPoints, $actualPoints * $rate);
        $this->totals->recalculate($cart->fresh(['items', 'coupons']));

        return ['points' => $actualPoints, 'amount_cents' => $actualPoints * $rate];
    }

    public function clear(Cart $cart): void
    {
        RedemptionState::clear($cart);
        $this->totals->recalculate($cart->fresh(['items', 'coupons']));
    }
}
