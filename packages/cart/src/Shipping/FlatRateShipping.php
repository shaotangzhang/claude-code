<?php

declare(strict_types=1);

namespace Acme\Cart\Shipping;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingCalculator;
use Acme\Contracts\Commerce\ShippingOption;

final class FlatRateShipping implements ShippingCalculator
{
    public function options(array $items, string $currency, ?Address $destination): array
    {
        $cents    = (int) config('acme.cart.shipping.flat_rate_cents', 0);
        $freeAt   = (int) config('acme.cart.shipping.free_above_cents', 0);
        $label    = (string) config('acme.cart.shipping.label', 'Standard shipping');
        $daysMin  = (int) config('acme.cart.shipping.days_min', 3);
        $daysMax  = (int) config('acme.cart.shipping.days_max', 7);

        // Items are passed by the calling service; we don't recompute subtotal
        // here — the caller already knows it. Free shipping is honoured via
        // a subtotal hint in items[0] if present (optional convention).
        if ($freeAt > 0 && isset($items['__subtotal_cents']) && $items['__subtotal_cents'] >= $freeAt) {
            $cents = 0;
        }

        return [
            new ShippingOption(
                key:              'standard',
                label:            $cents === 0 ? 'Free shipping' : $label,
                costCents:        $cents,
                currency:         $currency,
                estimatedDaysMin: $daysMin,
                estimatedDaysMax: $daysMax,
            ),
        ];
    }
}
