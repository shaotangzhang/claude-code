<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

/**
 * Order-side stock writer. Commerce ships a default implementation
 * (basic on_hand / reserved per SKU × warehouse). Replace it by
 * binding this interface to a smarter allocator:
 *
 *   - acme/inventory-fefo   — pick by shortest-expiry batch first
 *   - acme/inventory-lifo   — last-in, first-out (rare)
 *   - acme/inventory-zone   — by destination proximity
 *
 * Implementations MUST be transactional and idempotent — listeners
 * may fire twice (webhook retries) and these calls must not double-
 * reserve or double-ship.
 */
interface StockAllocator
{
    /**
     * @param  array<string,int>  $lines  sku_id => qty
     * @return bool  true if everything reserved; throws if any line short
     */
    public function reserveForOrder(string $orderId, array $lines): bool;

    /** Convert reservations to outbound (decrement on_hand). */
    public function shipForOrder(string $orderId): void;

    /** Release reservations without shipping (cart canceled, etc). */
    public function releaseForOrder(string $orderId): void;
}
