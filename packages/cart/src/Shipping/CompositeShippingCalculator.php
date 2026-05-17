<?php

declare(strict_types=1);

namespace Acme\Cart\Shipping;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingCalculator;

/**
 * Default `ShippingCalculator` binding for cart. Combines:
 *   1) the built-in FlatRateShipping (toggleable)
 *   2) every method registered in ShippingMethodRegistry
 *
 * Returning a UNION of options — checkout UI lists all, user picks one.
 */
final class CompositeShippingCalculator implements ShippingCalculator
{
    public function __construct(
        private readonly ShippingMethodRegistry $registry,
        private readonly FlatRateShipping $flat,
    ) {}

    public function options(array $items, string $currency, ?Address $destination): array
    {
        $subtotal = (int) ($items['__subtotal_cents'] ?? 0);
        $bare     = $items;
        unset($bare['__subtotal_cents']);
        $list     = array_values($bare);

        $out = [];
        if ((bool) config('acme.cart.shipping.builtin_flat_enabled', true)) {
            foreach ($this->flat->options($items, $currency, $destination) as $opt) {
                $out[] = $opt;
            }
        }

        foreach ($this->registry->all() as $method) {
            foreach ($method->rate($list, $currency, $destination, $subtotal) as $opt) {
                $out[] = $opt;
            }
        }

        return $out;
    }
}
