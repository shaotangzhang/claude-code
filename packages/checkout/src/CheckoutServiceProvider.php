<?php

declare(strict_types=1);

namespace Acme\Checkout;

use Acme\Checkout\Listeners\HandlePaymentSucceeded;
use Acme\Checkout\Services\CheckoutService;
use Acme\Checkout\Services\OrderService;
use Acme\Payments\Events\PaymentSucceeded;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

final class CheckoutServiceProvider extends PackageServiceProvider
{
    protected string $key = 'checkout';

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
        $this->app->singleton(CheckoutService::class);
        $this->app->singleton(OrderService::class);
    }

    protected function packageBoot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(PaymentSucceeded::class, [HandlePaymentSucceeded::class, 'handle']);
    }
}
