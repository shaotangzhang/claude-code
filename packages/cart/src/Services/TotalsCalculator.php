<?php

declare(strict_types=1);

namespace Acme\Cart\Services;

use Acme\Cart\Models\Cart;
use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingCalculator;
use Acme\Contracts\Commerce\TaxCalculator;

/**
 * Computes and stores denormalized totals on a Cart row.
 * Pure with respect to the persisted cart state at call time — caller
 * is responsible for invoking after mutating items / coupons / address.
 */
final class TotalsCalculator
{
    public function __construct(
        private readonly TaxCalculator $tax,
        private readonly ShippingCalculator $shipping,
    ) {}

    public function recalculate(Cart $cart, ?Address $destination = null, ?string $shippingOptionKey = null): Cart
    {
        $cart->loadMissing(['items', 'coupons']);

        $subtotal = (int) $cart->items->sum('line_total_cents');

        $discount = 0;
        foreach ($cart->coupons as $coupon) {
            $discount += $this->discountFor($subtotal, $coupon->type, (int) $coupon->value, $cart->currency);
        }
        $discount = min($discount, $subtotal);

        $taxable     = $subtotal - $discount;
        $taxCents    = $this->tax->calculate(max(0, $taxable), $cart->currency, $destination);

        $items = $cart->items->map(fn ($i) => [
            'sku_id'   => $i->sku_id,
            'quantity' => $i->quantity,
        ])->all();
        $items['__subtotal_cents'] = $taxable;

        $shippingOptions = $this->shipping->options($items, $cart->currency, $destination);
        $shippingCents   = 0;
        if ($shippingOptions) {
            $chosen = $shippingOptionKey
                ? (collect($shippingOptions)->firstWhere('key', $shippingOptionKey) ?? $shippingOptions[0])
                : $shippingOptions[0];
            $shippingCents = $chosen->costCents;
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
