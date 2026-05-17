<?php

declare(strict_types=1);

namespace Acme\Cart\Pricing;

use Acme\Catalog\Models\Sku;
use Acme\Contracts\Commerce\PriceResolver;

/**
 * Single-currency default. Returns the SKU's own price_cents only when
 * the requested currency matches the SKU's currency; null otherwise.
 *
 * Multi-currency-pricing replaces this binding with a price-book lookup.
 */
final class DefaultPriceResolver implements PriceResolver
{
    public function priceFor(string $skuId, string $currency): ?int
    {
        $sku = Sku::query()->find($skuId);
        if (! $sku || $sku->currency !== $currency) {
            return null;
        }

        return (int) $sku->price_cents;
    }

    public function listPriceFor(string $skuId, string $currency): ?int
    {
        $sku = Sku::query()->find($skuId);
        if (! $sku || $sku->currency !== $currency) {
            return null;
        }
        $list = $sku->list_price_cents;

        return $list === null ? null : (int) $list;
    }
}
