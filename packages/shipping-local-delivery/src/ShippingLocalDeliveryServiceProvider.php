<?php

declare(strict_types=1);

namespace Acme\ShippingLocalDelivery;

use Acme\Cart\Shipping\ShippingMethodRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class ShippingLocalDeliveryServiceProvider extends PackageServiceProvider
{
    protected string $key = 'shipping-local-delivery';

    protected bool $hasConfig = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(LocalDeliveryMethod::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(ShippingMethodRegistry::class, function (ShippingMethodRegistry $r): void {
            $r->register($this->app->make(LocalDeliveryMethod::class));
        });
    }
}
