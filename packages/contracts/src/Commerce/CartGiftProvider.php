<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * Anyone wanting to push a free gift item into a cart implements this.
 * Returns the set of desired gifts; cart reconciles (insert new, update
 * matching, remove stale by sourceKey).
 *
 * Implementations MUST be pure: no DB writes, deterministic across
 * recalcs in the same request. The cart pipeline performs the actual
 * insert/update/delete.
 */
interface CartGiftProvider
{
    /**
     * @param  array<int,array{sku_id:string,quantity:int,unit_price_cents:int,line_total_cents:int,currency:string,attrs?:array<string,mixed>}>  $items
     *         Only non-gift lines are passed in — providers see what the user actually added.
     * @return list<CartGiftInsert>
     */
    public function giftsFor(
        array $items,
        int $subtotalCents,
        string $currency,
        ?string $userId,
    ): array;
}
