<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * One concrete shipping method registered in the cart's registry.
 * Multiple methods can coexist; cart presents the union of options
 * at checkout and the user picks one by ShippingOption::$key.
 *
 * Real-world examples:
 *   - "standard" flat rate
 *   - "zones" country-zone matrix (acme/shipping-zones)
 *   - "weight" weight-bracket pricing (acme/shipping-weight)
 *   - "fedex" carrier API live rates (acme/shipping-fedex)
 */
interface ShippingMethod
{
    /** Stable identifier for this method, used in option keys and configs. */
    public function key(): string;

    /**
     * @param  list<array{sku_id:string,quantity:int}>  $items
     * @return list<ShippingOption>
     */
    public function rate(
        array $items,
        string $currency,
        ?Address $destination,
        int $subtotalCents,
    ): array;
}
