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
 * Pipeline:
 *   subtotal = Σ line totals
 *   discount = Σ coupon discounts  +  Σ adjustments where target=discount
 *   tax      = TaxCalculator(taxable)
 *   shipping = ShippingCalculator(chosen option) + Σ adjustments where target=shipping
 *   total    = taxable + tax + shipping
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
    ) {}

    public function recalculate(Cart $cart, ?Address $destination = null, ?string $shippingOptionKey = null): Cart
    {
        $cart->loadMissing(['items', 'coupons']);

        $subtotal = (int) $cart->items->sum('line_total_cents');

        // 1. Coupon discounts
        $couponDiscount = 0;
        foreach ($cart->coupons as $coupon) {
            $couponDiscount += $this->discountFor($subtotal, $coupon->type, (int) $coupon->value, $cart->currency);
        }

        // 2. Provider adjustments (campaigns, loyalty redemption, member discount, ...)
        $providerItems = $cart->items->map(fn ($i) => [
            'sku_id'           => $i->sku_id,
            'quantity'         => $i->quantity,
            'unit_price_cents' => $i->unit_price_cents,
            'line_total_cents' => $i->line_total_cents,
            'currency'         => $i->currency,
            'attrs'            => $i->attrs_json ?? [],
        ])->values()->all();

        $extraDiscount = 0;
        $extraShipping = 0;
        foreach ($this->adjustments->all() as $provider) {
            foreach ($provider->adjustmentsFor($providerItems, $subtotal, $cart->currency, $cart->user_id) as $adj) {
                /** @var CartAdjustment $adj */
                if ($adj->target === CartAdjustment::TARGET_DISCOUNT) {
                    // Discounts are stored as positive cents; providers send negative.
                    $extraDiscount += abs($adj->amountCents);
                } elseif ($adj->target === CartAdjustment::TARGET_SHIPPING) {
                    $extraShipping += $adj->amountCents;
                }
            }
        }

        $discount = min($couponDiscount + $extraDiscount, $subtotal);

        $taxable  = $subtotal - $discount;
        $taxCents = $this->tax->calculate(max(0, $taxable), $cart->currency, $destination);

        $shippingInput = $providerItems + ['__subtotal_cents' => $taxable];
        $shippingOptions = $this->shipping->options($shippingInput, $cart->currency, $destination);
        $shippingCents   = 0;
        if ($shippingOptions) {
            $chosen = $shippingOptionKey
                ? (collect($shippingOptions)->firstWhere('key', $shippingOptionKey) ?? $shippingOptions[0])
                : $shippingOptions[0];
            $shippingCents = $chosen->costCents;
        }
        $shippingCents = max(0, $shippingCents + $extraShipping);

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
