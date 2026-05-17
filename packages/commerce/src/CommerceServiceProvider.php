<?php

declare(strict_types=1);

namespace Acme\Commerce;

use Acme\Cart\Adjustments\AdjustmentRegistry;
use Acme\Checkout\Events\OrderCanceled;
use Acme\Checkout\Events\OrderFulfilled;
use Acme\Checkout\Events\OrderPaid;
use Acme\Commerce\Campaigns\CampaignProvider;
use Acme\Commerce\Campaigns\Evaluators\BundleEvaluator;
use Acme\Commerce\Campaigns\Evaluators\TimedDiscountEvaluator;
use Acme\Commerce\Campaigns\RuleEvaluator;
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

        $this->app->tag([
            BundleEvaluator::class,
            TimedDiscountEvaluator::class,
        ], 'acme.commerce.campaign_evaluators');

        $this->app->singleton(CampaignProvider::class, function ($app) {
            /** @var iterable<RuleEvaluator> $evaluators */
            $evaluators = $app->tagged('acme.commerce.campaign_evaluators');

            return new CampaignProvider($evaluators);
        });
    }

    protected function packageBoot(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(OrderPaid::class,      [HandleOrderPaid::class,      'handle']);
        $events->listen(OrderFulfilled::class, [HandleOrderFulfilled::class, 'handle']);
        $events->listen(OrderCanceled::class,  [HandleOrderCanceled::class,  'handle']);

        // Hook into cart's adjustment registry so campaigns auto-apply on every recalc.
        $this->app->resolving(AdjustmentRegistry::class, function (AdjustmentRegistry $reg): void {
            $reg->register($this->app->make(CampaignProvider::class));
        });
    }
}
