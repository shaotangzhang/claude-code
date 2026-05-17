<?php

declare(strict_types=1);

namespace Acme\ShippingFree;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingMethod;
use Acme\Contracts\Commerce\ShippingOption;

final class FreeShippingMethod implements ShippingMethod
{
    public function key(): string { return 'free'; }

    public function rate(array $items, string $currency, ?Address $destination, int $subtotalCents): array
    {
        $min = (int) config('acme.shipping-free.min_subtotal_cents', 0);
        if ($subtotalCents < $min) {
            return [];
        }

        $countries = array_map('strtoupper', (array) config('acme.shipping-free.countries', []));
        if ($countries && $destination && ! in_array(strtoupper($destination->country), $countries, true)) {
            return [];
        }

        return [new ShippingOption(
            key:       'free',
            label:     (string) config('acme.shipping-free.label', 'Free shipping'),
            costCents: 0,
            currency:  $currency,
        )];
    }
}
