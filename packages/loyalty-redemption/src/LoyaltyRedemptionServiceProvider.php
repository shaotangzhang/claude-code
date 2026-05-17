<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption;

use Acme\Cart\Adjustments\AdjustmentRegistry;
use Acme\Checkout\Events\OrderPaid;
use Acme\LoyaltyRedemption\Listeners\HandleOrderPaid;
use Acme\LoyaltyRedemption\Providers\LoyaltyRedemptionProvider;
use Acme\LoyaltyRedemption\Services\LoyaltyRedemptionService;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

final class LoyaltyRedemptionServiceProvider extends PackageServiceProvider
{
    protected string $key = 'loyalty-redemption';

    protected bool $hasMigrations = false;
    protected bool $hasRoutesWeb  = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(LoyaltyRedemptionService::class);
        $this->app->singleton(LoyaltyRedemptionProvider::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(AdjustmentRegistry::class, function (AdjustmentRegistry $r): void {
            $r->register($this->app->make(LoyaltyRedemptionProvider::class));
        });

        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(OrderPaid::class, [HandleOrderPaid::class, 'handle']);
    }
}
