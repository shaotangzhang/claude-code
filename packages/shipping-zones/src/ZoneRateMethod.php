<?php

declare(strict_types=1);

namespace Acme\ShippingZones;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingMethod;
use Acme\Contracts\Commerce\ShippingOption;
use Acme\ShippingZones\Models\Zone;
use Illuminate\Support\Facades\DB;

/**
 * Looks up the destination country in `acme_shipping_zone_countries`,
 * loads the matching zone's rates, returns those that match currency
 * and subtotal bounds.
 *
 * If destination is null OR no zone covers the country, returns [].
 * Multiple rates per zone are common ("standard" + "express").
 */
final class ZoneRateMethod implements ShippingMethod
{
    public function key(): string { return 'zone'; }

    public function rate(array $items, string $currency, ?Address $destination, int $subtotalCents): array
    {
        if (! $destination || $destination->country === '') {
            return [];
        }

        $country = strtoupper($destination->country);
        $zoneIds = DB::table('acme_shipping_zone_countries')
            ->where('country_code', $country)->pluck('zone_id');
        if ($zoneIds->isEmpty()) {
            return [];
        }

        $zones = Zone::query()->whereIn('id', $zoneIds)->where('active', true)->with('rates')->get();

        $out = [];
        foreach ($zones as $zone) {
            foreach ($zone->rates as $rate) {
                if ($rate->currency !== $currency) continue;
                if (! $rate->appliesTo($subtotalCents))  continue;

                $out[] = new ShippingOption(
                    key:              "zone-{$zone->key}-{$rate->key}",
                    label:            "{$zone->name} · {$rate->label}",
                    costCents:        (int) $rate->cost_cents,
                    currency:         $rate->currency,
                    estimatedDaysMin: $rate->days_min,
                    estimatedDaysMax: $rate->days_max,
                );
            }
        }

        return $out;
    }
}
