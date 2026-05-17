<?php

declare(strict_types=1);

namespace Acme\AbandonedCart;

use Acme\AbandonedCart\Console\TickCommand;
use Acme\AbandonedCart\Events\CartAbandoned;
use Acme\AbandonedCart\Listeners\SendRecoveryReminder;
use Acme\AbandonedCart\Services\AbandonmentService;
use Acme\AbandonedCart\Services\CouponMinter;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

final class AbandonedCartServiceProvider extends PackageServiceProvider
{
    protected string $key = 'abandoned-cart';

    protected bool $hasRoutesWeb = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(CouponMinter::class);
        $this->app->singleton(AbandonmentService::class);
    }

    protected function packageBoot(): void
    {
        // Provide a sensible default channel mapping for the new event
        // without forcing hosts to edit acme.notifications.events.
        $existing = config('acme.notifications.events.cart.abandoned');
        if ($existing === null) {
            config(['acme.notifications.events.cart.abandoned' =>
                (array) config('acme.abandoned-cart.default_channels', ['mail'])]);
        }

        /** @var Dispatcher $events */
        $events = $this->app->make(Dispatcher::class);
        $events->listen(CartAbandoned::class, [SendRecoveryReminder::class, 'handle']);

        if ($this->app->runningInConsole()) {
            $this->commands([TickCommand::class]);
        }
    }
}
