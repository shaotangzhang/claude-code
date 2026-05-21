<?php

declare(strict_types=1);

namespace Acme\MultiCurrencyPricing;

use Acme\Contracts\Commerce\PriceResolver;
use Acme\Starter\Support\PackageServiceProvider;

final class MultiCurrencyPricingServiceProvider extends PackageServiceProvider
{
    protected string $key = 'multi-currency-pricing';

    protected bool $hasConfig = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        // Rebind PriceResolver — cart's DefaultPriceResolver is replaced
        // by ours for every CartService call.
        $this->app->singleton(PriceResolver::class, PriceBookResolver::class);
        $this->app->singleton(PriceBookResolver::class);
    }
}
