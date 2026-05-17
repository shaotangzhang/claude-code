<?php

declare(strict_types=1);

namespace Acme\ShippingPickup;

use Acme\Cart\Shipping\ShippingMethodRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class ShippingPickupServiceProvider extends PackageServiceProvider
{
    protected string $key = 'shipping-pickup';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(PickupMethod::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(ShippingMethodRegistry::class, function (ShippingMethodRegistry $r): void {
            $r->register($this->app->make(PickupMethod::class));
        });
    }
}
