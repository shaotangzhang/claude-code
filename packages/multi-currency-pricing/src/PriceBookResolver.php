<?php

declare(strict_types=1);

namespace Acme\MultiCurrencyPricing;

use Acme\Catalog\Models\Sku;
use Acme\Contracts\Commerce\PriceResolver;
use Acme\MultiCurrencyPricing\Models\SkuPrice;

/**
 * Table-driven price resolver. Looks up an active row in
 * acme_pricing_sku_prices for (sku, currency). Falls back to SKU's
 * own price_cents column if and only if the SKU's currency matches —
 * so projects can stage the migration to a price book without breaking
 * existing single-currency SKUs.
 */
final class PriceBookResolver implements PriceResolver
{
    public function priceFor(string $skuId, string $currency): ?int
    {
        $row = SkuPrice::query()
            ->where('sku_id', $skuId)->where('currency', $currency)->where('active', true)->first();
        if ($row) {
            return (int) $row->price_cents;
        }

        $sku = Sku::query()->find($skuId);
        if ($sku && $sku->currency === $currency) {
            return (int) $sku->price_cents;
        }

        return null;
    }

    public function listPriceFor(string $skuId, string $currency): ?int
    {
        $row = SkuPrice::query()
            ->where('sku_id', $skuId)->where('currency', $currency)->where('active', true)->first();
        if ($row && $row->list_price_cents !== null) {
            return (int) $row->list_price_cents;
        }

        $sku = Sku::query()->find($skuId);
        if ($sku && $sku->currency === $currency && $sku->list_price_cents !== null) {
            return (int) $sku->list_price_cents;
        }

        return null;
    }
}
