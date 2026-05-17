<?php

declare(strict_types=1);

namespace Acme\Cart\Shipping;

use Acme\Contracts\Commerce\ShippingMethod;

/**
 * Registry of installed shipping methods. Each acme/shipping-* package
 * registers its method during boot:
 *
 *   $this->app->resolving(ShippingMethodRegistry::class, function ($r) {
 *       $r->register($this->app->make(MyMethod::class));
 *   });
 */
final class ShippingMethodRegistry
{
    /** @var list<ShippingMethod> */
    private array $methods = [];

    public function register(ShippingMethod $method): void
    {
        $this->methods[] = $method;
    }

    /** @return list<ShippingMethod> */
    public function all(): array
    {
        return $this->methods;
    }
}
