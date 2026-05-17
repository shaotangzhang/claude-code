<?php

declare(strict_types=1);

namespace Acme\NotificationsWebhook;

use Acme\Notifications\ChannelRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;

final class NotificationsWebhookServiceProvider extends PackageServiceProvider
{
    protected string $key = 'notifications-webhook';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(WebhookChannel::class, function ($app) {
            return new WebhookChannel($app->make(Http::class), $app->make(LoggerInterface::class));
        });
    }

    protected function packageBoot(): void
    {
        $this->app->make(ChannelRegistry::class)->register($this->app->make(WebhookChannel::class));
    }
}
