<?php

declare(strict_types=1);

namespace Acme\Membership;

use Acme\Contracts\Cms\BlockRegistry;
use Acme\Membership\Blocks\PlanGridBlock;
use Acme\Membership\Console\TickCommand;
use Acme\Membership\Events\PaymentReceived;
use Acme\Membership\Events\SubscriptionExpired;
use Acme\Membership\Events\SubscriptionStarted;
use Acme\Membership\Listeners\HandlePaymentReceived;
use Acme\Membership\Listeners\SyncTierRole;
use Acme\Membership\Services\SubscriptionService;
use Acme\Membership\Services\TierResolver;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

final class MembershipServiceProvider extends PackageServiceProvider
{
    protected string $key = 'membership';

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
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(TierResolver::class);
    }

    protected function packageBoot(): void
    {
        $this->app->resolving(BlockRegistry::class, function (BlockRegistry $reg): void {
            $reg->register(PlanGridBlock::class);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([TickCommand::class]);
        }

        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(PaymentReceived::class,  [HandlePaymentReceived::class, 'handle']);
        $events->listen(SubscriptionStarted::class, [SyncTierRole::class, 'onStarted']);
        $events->listen(SubscriptionExpired::class, [SyncTierRole::class, 'onExpired']);
    }
}
