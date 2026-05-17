<?php

declare(strict_types=1);

namespace Acme\Commerce;

use Acme\Checkout\Events\OrderCanceled;
use Acme\Checkout\Events\OrderFulfilled;
use Acme\Checkout\Events\OrderPaid;
use Acme\Commerce\Listeners\HandleOrderCanceled;
use Acme\Commerce\Listeners\HandleOrderFulfilled;
use Acme\Commerce\Listeners\HandleOrderPaid;
use Acme\Commerce\Services\LoyaltyService;
use Acme\Commerce\Services\ReturnService;
use Acme\Commerce\Services\ReviewService;
use Acme\Commerce\Services\StockService;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

final class CommerceServiceProvider extends PackageServiceProvider
{
    protected string $key = 'commerce';

    protected bool $hasViews        = true;
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
        $this->app->singleton(StockService::class);
        $this->app->singleton(LoyaltyService::class);
        $this->app->singleton(ReturnService::class);
        $this->app->singleton(ReviewService::class);
    }

    protected function packageBoot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(OrderPaid::class,      [HandleOrderPaid::class,      'handle']);
        $events->listen(OrderFulfilled::class, [HandleOrderFulfilled::class, 'handle']);
        $events->listen(OrderCanceled::class,  [HandleOrderCanceled::class,  'handle']);
    }
}
