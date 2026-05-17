<?php

declare(strict_types=1);

namespace Acme\ShippingFree;

use Acme\Cart\Shipping\ShippingMethodRegistry;
use Acme\Starter\Support\PackageServiceProvider;

final class ShippingFreeServiceProvider extends PackageServiceProvider
{
    protected string $key = 'shipping-free';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(FreeShippingMethod::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(ShippingMethodRegistry::class, function (ShippingMethodRegistry $r): void {
            $r->register($this->app->make(FreeShippingMethod::class));
        });
    }
}
