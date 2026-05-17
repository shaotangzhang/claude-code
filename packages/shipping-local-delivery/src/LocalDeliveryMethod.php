<?php

declare(strict_types=1);

namespace Acme\ShippingLocalDelivery;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingMethod;
use Acme\Contracts\Commerce\ShippingOption;
use Acme\ShippingLocalDelivery\Models\LocalZone;

/**
 * Picks every active zone matching the destination's country + postal
 * prefix; emits each of its rates whose currency matches and whose
 * subtotal floor is met.
 *
 * eta_minutes_*  is mapped onto ShippingOption days fields for display
 * convenience (renderer can choose how to phrase).
 */
final class LocalDeliveryMethod implements ShippingMethod
{
    public function key(): string { return 'local'; }

    public function rate(array $items, string $currency, ?Address $destination, int $subtotalCents): array
    {
        if (! $destination || ! $destination->country || ! $destination->postalCode) {
            return [];
        }

        $zones = LocalZone::query()->where('active', true)
            ->where('country', strtoupper($destination->country))
            ->with('rates')->get();

        $out = [];
        foreach ($zones as $zone) {
            if (! $zone->matchesPostal($destination->postalCode)) {
                continue;
            }
            foreach ($zone->rates as $r) {
                if ($r->currency !== $currency || ! $r->appliesTo($subtotalCents)) {
                    continue;
                }
                $out[] = new ShippingOption(
                    key:              "local-{$zone->key}-{$r->key}",
                    label:            "{$zone->name} · {$r->label}",
                    costCents:        (int) $r->cost_cents,
                    currency:         $r->currency,
                    // Storing minutes in days field is a slight abuse, but
                    // ShippingOption doesn't yet have a minutes pair.
                    estimatedDaysMin: $r->eta_minutes_min === null ? null : 0,
                    estimatedDaysMax: $r->eta_minutes_max === null ? null : 1,
                );
            }
        }

        return $out;
    }
}
