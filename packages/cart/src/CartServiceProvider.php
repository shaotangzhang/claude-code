<?php

declare(strict_types=1);

namespace Acme\Cart;

use Acme\Cart\Adjustments\AdjustmentRegistry;
use Acme\Cart\Blocks\CartSummaryBlock;
use Acme\Cart\Listeners\MergeOnLogin;
use Acme\Cart\Services\CartMerger;
use Acme\Cart\Services\CartService;
use Acme\Cart\Services\CouponService;
use Acme\Cart\Services\TotalsCalculator;
use Acme\Cart\Shipping\CompositeShippingCalculator;
use Acme\Cart\Shipping\FlatRateShipping;
use Acme\Cart\Shipping\ShippingMethodRegistry;
use Acme\Cart\Tax\FlatRateTax;
use Acme\Contracts\Cms\BlockRegistry;
use Acme\Contracts\Commerce\ShippingCalculator;
use Acme\Contracts\Commerce\TaxCalculator;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;

final class CartServiceProvider extends PackageServiceProvider
{
    protected string $key = 'cart';

    protected bool $hasViews        = true;
    protected bool $hasRoutesWeb    = true;
    protected bool $hasRoutesAdmin  = true;
    protected bool $hasCapabilities = true;
    protected bool $hasNavigation   = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        // Defaults — override by binding TaxCalculator to your own
        // implementation in the host ServiceProvider. For shipping, install
        // an acme/shipping-* package to register additional ShippingMethods.
        $this->app->singleton(TaxCalculator::class, FlatRateTax::class);

        $this->app->singleton(FlatRateShipping::class);
        $this->app->singleton(ShippingMethodRegistry::class);
        $this->app->singleton(ShippingCalculator::class, CompositeShippingCalculator::class);

        $this->app->singleton(AdjustmentRegistry::class);
        $this->app->singleton(TotalsCalculator::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(CouponService::class);
        $this->app->singleton(CartMerger::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(BlockRegistry::class, function (BlockRegistry $reg): void {
            $reg->register(CartSummaryBlock::class);
        });

        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(Login::class, [MergeOnLogin::class, 'handle']);
    }
}
