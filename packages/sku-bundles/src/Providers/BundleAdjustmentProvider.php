<?php

declare(strict_types=1);

namespace Acme\SkuBundles\Providers;

use Acme\Cart\Models\CartItem;
use Acme\Contracts\Commerce\CartAdjustment;
use Acme\Contracts\Commerce\CartAdjustmentProvider;
use Acme\SkuBundles\Models\Bundle;

/**
 * For each bundle currently represented in the cart (grouped by
 * bundle_source_key), emits a discount adjustment equal to:
 *
 *   sum(child line_total_cents)  −  bundle.price_cents
 *
 * Result: cart shows children at their individual prices, then a
 * discount line reading "Summer Pack bundle (save $X.YY)".
 *
 * Reads the cart's items directly because the items array passed in
 * doesn't carry the bundle_source_key field; in 0.2 we'll widen the
 * contract to include line metadata.
 */
final class BundleAdjustmentProvider implements CartAdjustmentProvider
{
    public function adjustmentsFor(array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        if ($items === []) {
            return [];
        }

        $skuIds = array_unique(array_column($items, 'sku_id'));
        $bundleLines = CartItem::query()
            ->whereIn('sku_id', $skuIds)
            ->whereNotNull('bundle_source_key')
            ->where('currency', $currency)
            ->get(['sku_id', 'quantity', 'unit_price_cents', 'line_total_cents', 'bundle_source_key']);

        if ($bundleLines->isEmpty()) {
            return [];
        }

        $out = [];
        foreach ($bundleLines->groupBy('bundle_source_key') as $sourceKey => $lines) {
            // sourceKey format: "bundle:<bundle.key>:<suffix>"
            $bundleKey = explode(':', (string) $sourceKey, 3)[1] ?? null;
            if ($bundleKey === null) {
                continue;
            }
            $bundle = Bundle::query()->where('key', $bundleKey)->where('currency', $currency)->first();
            if (! $bundle || ! $bundle->active) {
                continue;
            }

            $sumOfChildren = (int) $lines->sum('line_total_cents');
            $delta         = (int) $bundle->price_cents - $sumOfChildren;
            if ($delta >= 0) {
                continue; // no saving — don't pollute display
            }

            $out[] = new CartAdjustment(
                sourceKey:   (string) $sourceKey,
                description: "{$bundle->name} bundle",
                amountCents: $delta,                              // negative
            );
        }

        return $out;
    }
}
