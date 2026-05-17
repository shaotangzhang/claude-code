<?php

declare(strict_types=1);

namespace Acme\Cart\Adjustments;

use Acme\Contracts\Commerce\CartGiftProvider;

/**
 * Registry of providers that want to push free-gift lines into carts.
 * Sibling of AdjustmentRegistry but for line-insert semantics.
 *
 * Each downstream package (commerce campaigns today, "gift with email
 * signup" tomorrow) registers its provider via container resolving.
 */
final class GiftRegistry
{
    /** @var list<CartGiftProvider> */
    private array $providers = [];

    public function register(CartGiftProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return list<CartGiftProvider> */
    public function all(): array
    {
        return $this->providers;
    }
}
