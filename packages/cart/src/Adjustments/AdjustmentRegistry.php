<?php

declare(strict_types=1);

namespace Acme\Cart\Adjustments;

use Acme\Contracts\Commerce\CartAdjustmentProvider;

/**
 * Registry of providers that contribute discounts / shipping / tax
 * adjustments on top of a cart's coupon-based totals.
 *
 * Each downstream package (commerce campaigns, loyalty, membership
 * discount, ...) registers its provider during boot:
 *
 *   $this->app->resolving(AdjustmentRegistry::class, function ($r) {
 *       $r->register(new MyProvider(...));
 *   });
 */
final class AdjustmentRegistry
{
    /** @var list<CartAdjustmentProvider> */
    private array $providers = [];

    public function register(CartAdjustmentProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return list<CartAdjustmentProvider> */
    public function all(): array
    {
        return $this->providers;
    }
}
