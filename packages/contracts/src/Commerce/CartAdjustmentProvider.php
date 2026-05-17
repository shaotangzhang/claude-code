<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * Anyone wanting to influence a cart's totals — campaigns, loyalty
 * redemption, membership discount, custom rules — implements this and
 * registers with the cart's adjustment registry.
 *
 * Implementations MUST be:
 *   - pure with respect to the cart snapshot (no DB writes)
 *   - deterministic across multiple calls in the same request
 *   - tolerant of being called during partial cart state
 *
 * The runtime hands you a typed Cart-like object via the iterator below;
 * we deliberately don't bind to acme/cart's Eloquent model here so the
 * contract stays in `acme/contracts` with zero downstream deps.
 */
interface CartAdjustmentProvider
{
    /**
     * @param  array<int, array{sku_id:string, quantity:int, unit_price_cents:int, line_total_cents:int, currency:string, attrs?:array<string,mixed>}>  $items
     * @return list<CartAdjustment>
     */
    public function adjustmentsFor(
        array $items,
        int $subtotalCents,
        string $currency,
        ?string $userId,
    ): array;
}
