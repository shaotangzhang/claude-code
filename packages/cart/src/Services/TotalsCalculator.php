<?php

declare(strict_types=1);

namespace Acme\Cart\Services;

use Acme\Cart\Adjustments\AdjustmentRegistry;
use Acme\Cart\Models\Cart;
use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\CartAdjustment;
use Acme\Contracts\Commerce\ShippingCalculator;
use Acme\Contracts\Commerce\TaxCalculator;

/**
 * Computes and stores denormalized totals on a Cart row.
 *
 * Pipeline (in order):
 *   0. GiftSync::reconcile         (insert/remove gift lines from providers)
 *   1. subtotal = Σ all line totals (gifts at unit price)
 *   2. discount  = Σ coupon discounts
 *                + Σ adjustment-provider discounts
 *                + auto-discount of every gift line's value  ← so gifts net to 0
 *   3. tax       = TaxCalculator(taxable)
 *   4. shipping  = ShippingCalculator + provider shipping adj. + force-free flag
 *   5. total     = taxable + tax + shipping
 *
 * Pure with respect to the persisted cart state at call time — caller
 * is responsible for invoking after mutating items / coupons / address.
 */
final class TotalsCalculator
{
    public function __construct(
        private readonly TaxCalculator $tax,
        private readonly ShippingCalculator $shipping,
        private readonly AdjustmentRegistry $adjustments,
        private readonly GiftSync $gifts,
    ) {}

    public function recalculate(Cart $cart, ?Address $destination = null, ?string $shippingOptionKey = null): Cart
    {
        // 0. Reconcile gift lines first; refresh cart.items afterwards.
        $this->gifts->reconcile($cart);
        $cart->loadMissing(['items', 'coupons']);

        $subtotal      = (int) $cart->items->sum('line_total_cents');
        $giftSubtotal  = (int) $cart->items->where('is_gift', true)->sum('line_total_cents');

        // 1. Coupon discounts — based on NON-gift subtotal so coupons can't
        //    accidentally "discount" the freebies further.
        $payableSubtotal = $subtotal - $giftSubtotal;
        $couponDiscount  = 0;
        foreach ($cart->coupons as $coupon) {
            $couponDiscount += $this->discountFor($payableSubtotal, $coupon->type, (int) $coupon->value, $cart->currency);
        }

        // 2. Provider adjustments (campaigns, loyalty redemption, member discount, ...).
        $nonGiftItems = $cart->items->where('is_gift', false)->map(fn ($i) => [
            'sku_id'           => $i->sku_id,
            'quantity'         => $i->quantity,
            'unit_price_cents' => $i->unit_price_cents,
            'line_total_cents' => $i->line_total_cents,
            'currency'         => $i->currency,
            'attrs'            => $i->attrs_json ?? [],
        ])->values()->all();

        $extraDiscount     = 0;
        $extraShipping     = 0;
        $forceFreeShipping = false;
        foreach ($this->adjustments->all() as $provider) {
            foreach ($provider->adjustmentsFor($nonGiftItems, $payableSubtotal, $cart->currency, $cart->user_id) as $adj) {
                /** @var CartAdjustment $adj */
                if ($adj->target === CartAdjustment::TARGET_DISCOUNT) {
                    $extraDiscount += abs($adj->amountCents);
                } elseif ($adj->target === CartAdjustment::TARGET_SHIPPING) {
                    $extraShipping += $adj->amountCents;
                } elseif ($adj->target === CartAdjustment::TARGET_SHIPPING_FREE) {
                    $forceFreeShipping = true;
                }
            }
        }

        // Auto-discount for every gift line — net effect of gifts on total = 0.
        $autoGiftDiscount = $giftSubtotal;

        $discount = min($couponDiscount + $extraDiscount + $autoGiftDiscount, $subtotal);

        $taxable  = $subtotal - $discount;
        $taxCents = $this->tax->calculate(max(0, $taxable), $cart->currency, $destination);

        $shippingInput = $nonGiftItems + ['__subtotal_cents' => $taxable];
        $shippingOptions = $this->shipping->options($shippingInput, $cart->currency, $destination);
        $shippingCents   = 0;
        if ($shippingOptions) {
            $chosen = $shippingOptionKey
                ? (collect($shippingOptions)->firstWhere('key', $shippingOptionKey) ?? $shippingOptions[0])
                : $shippingOptions[0];
            $shippingCents = $chosen->costCents;
        }
        $shippingCents = max(0, $shippingCents + $extraShipping);
        if ($forceFreeShipping) {
            $shippingCents = 0;
        }

        $cart->subtotal_cents = $subtotal;
        $cart->discount_cents = $discount;
        $cart->tax_cents      = $taxCents;
        $cart->shipping_cents = $shippingCents;
        $cart->total_cents    = max(0, $taxable + $taxCents + $shippingCents);
        $cart->save();

        return $cart;
    }

    private function discountFor(int $subtotalCents, string $type, int $value, string $currency): int
    {
        return match ($type) {
            'percent' => intdiv($subtotalCents * max(0, min(100, $value)), 100),
            'fixed'   => max(0, $value),
            default   => 0,
        };
    }
}
