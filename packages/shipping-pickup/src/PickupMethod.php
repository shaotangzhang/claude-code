<?php

declare(strict_types=1);

namespace Acme\ShippingPickup;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingMethod;
use Acme\Contracts\Commerce\ShippingOption;
use Acme\ShippingPickup\Models\PickupLocation;

/**
 * Each active pickup location becomes a free shipping option labeled
 * "Pick up at <name>". If a destination country is given, only same-
 * country locations are offered (cross-border pickup is silly).
 */
final class PickupMethod implements ShippingMethod
{
    public function key(): string { return 'pickup'; }

    public function rate(array $items, string $currency, ?Address $destination, int $subtotalCents): array
    {
        $q = PickupLocation::query()->where('active', true);
        if ($destination && $destination->country) {
            $q->where(fn ($w) => $w->whereNull('country')->orWhere('country', strtoupper($destination->country)));
        }

        $out = [];
        foreach ($q->get() as $loc) {
            $out[] = new ShippingOption(
                key:              "pickup-{$loc->key}",
                label:            "Pick up at {$loc->name}" . ($loc->city ? " ({$loc->city})" : ''),
                costCents:        0,
                currency:         $currency,
                estimatedDaysMin: $loc->ready_days_min,
                estimatedDaysMax: $loc->ready_days_max,
            );
        }

        return $out;
    }
}
