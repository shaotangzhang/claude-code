<?php

declare(strict_types=1);

namespace Acme\Notifications;

use Acme\Blog\Events\ArticlePublished;
use Acme\Checkout\Events\OrderCanceled;
use Acme\Checkout\Events\OrderFulfilled;
use Acme\Checkout\Events\OrderPaid;
use Acme\Checkout\Events\OrderPlaced;
use Acme\Commerce\Events\ReturnRequested;
use Acme\Commerce\Events\StockLow;
use Acme\Notifications\Channels\LogChannel;
use Acme\Notifications\Channels\MailChannel;
use Acme\Notifications\Listeners\BlogListeners;
use Acme\Notifications\Listeners\CommerceListeners;
use Acme\Notifications\Listeners\OrderListeners;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Events\Dispatcher as Events;

final class NotificationsServiceProvider extends PackageServiceProvider
{
    protected string $key = 'notifications';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(ChannelRegistry::class);
        $this->app->singleton(Dispatcher::class);
        $this->app->singleton(MailChannel::class);
        $this->app->singleton(LogChannel::class);
    }

    protected function packageBoot(): void
    {
        $registry = $this->app->make(ChannelRegistry::class);
        $registry->register($this->app->make(MailChannel::class));
        $registry->register($this->app->make(LogChannel::class));

        /** @var Events $events */
        $events = $this->app->make(Events::class);

        $events->listen(OrderPlaced::class,    [OrderListeners::class, 'onPlaced']);
        $events->listen(OrderPaid::class,      [OrderListeners::class, 'onPaid']);
        $events->listen(OrderFulfilled::class, [OrderListeners::class, 'onFulfilled']);
        $events->listen(OrderCanceled::class,  [OrderListeners::class, 'onCanceled']);

        $events->listen(ReturnRequested::class, [CommerceListeners::class, 'onReturnRequested']);
        $events->listen(StockLow::class,        [CommerceListeners::class, 'onStockLow']);

        $events->listen(ArticlePublished::class, [BlogListeners::class, 'onArticlePublished']);
    }
}
