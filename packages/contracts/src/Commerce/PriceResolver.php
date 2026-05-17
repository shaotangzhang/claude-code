<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * Resolves the current effective price (in cents) for a given SKU in a
 * given currency. Cart calls this when adding a line so the snapshot
 * unit_price_cents reflects the active price book.
 *
 * Default implementation in cart returns the SKU's own price_cents
 * column (single-currency). Bind your own to swap in:
 *   - multi-currency-pricing (per-currency price book)
 *   - tier-pricing (price depends on member tier)
 *   - dynamic-pricing (real-time API)
 */
interface PriceResolver
{
    /**
     * @return int|null  null = no price configured for this currency;
     *                   caller decides what to do (skip / refuse / fall back).
     */
    public function priceFor(string $skuId, string $currency): ?int;

    /** Same shape for "crossed-out" / list price, optional. */
    public function listPriceFor(string $skuId, string $currency): ?int;
}
