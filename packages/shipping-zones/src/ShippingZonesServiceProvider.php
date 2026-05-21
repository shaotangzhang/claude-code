<?php

declare(strict_types=1);

namespace Acme\ShippingZones;

use Acme\Cart\Shipping\ShippingMethodRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class ShippingZonesServiceProvider extends PackageServiceProvider
{
    protected string $key = 'shipping-zones';

    protected bool $hasConfig = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(ZoneRateMethod::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(ShippingMethodRegistry::class, function (ShippingMethodRegistry $r): void {
            $r->register($this->app->make(ZoneRateMethod::class));
        });
    }
}
