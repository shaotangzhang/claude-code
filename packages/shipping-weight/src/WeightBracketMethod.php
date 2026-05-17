<?php

declare(strict_types=1);

namespace Acme\ShippingWeight;

use Acme\Contracts\Commerce\Address;
use Acme\Contracts\Commerce\ShippingMethod;
use Acme\Contracts\Commerce\ShippingOption;
use Acme\ShippingWeight\Models\SkuWeight;
use Acme\ShippingWeight\Models\WeightBracket;

/**
 * Sums total weight across cart items (from acme_shipping_sku_weights),
 * picks every active bracket that covers the total and matches currency.
 *
 * Items missing a SkuWeight row contribute 0 g (the package will not
 * fabricate weights). For accurate quotes, ensure every shippable SKU
 * has a SkuWeight row.
 */
final class WeightBracketMethod implements ShippingMethod
{
    public function key(): string { return 'weight'; }

    public function rate(array $items, string $currency, ?Address $destination, int $subtotalCents): array
    {
        $totalG = $this->totalWeight($items);
        if ($totalG === 0) {
            return [];
        }

        $brackets = WeightBracket::query()
            ->where('active', true)
            ->where('currency', $currency)
            ->get();

        $out = [];
        foreach ($brackets as $b) {
            if (! $b->matches($totalG)) continue;

            $out[] = new ShippingOption(
                key:              "weight-{$b->key}",
                label:            "{$b->label} ({$totalG} g)",
                costCents:        (int) $b->cost_cents,
                currency:         $b->currency,
                estimatedDaysMin: $b->days_min,
                estimatedDaysMax: $b->days_max,
            );
        }

        return $out;
    }

    /** @param list<array{sku_id:string,quantity:int}> $items */
    private function totalWeight(array $items): int
    {
        if (! $items) return 0;

        $ids     = array_unique(array_column($items, 'sku_id'));
        $weights = SkuWeight::query()->whereIn('sku_id', $ids)->pluck('weight_g', 'sku_id');

        $total = 0;
        foreach ($items as $i) {
            $total += (int) ($weights[$i['sku_id']] ?? 0) * max(1, (int) $i['quantity']);
        }

        return $total;
    }
}
