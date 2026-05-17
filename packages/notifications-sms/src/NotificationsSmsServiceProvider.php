<?php

declare(strict_types=1);

namespace Acme\NotificationsSms;

use Acme\Notifications\ChannelRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;

final class NotificationsSmsServiceProvider extends PackageServiceProvider
{
    protected string $key = 'notifications-sms';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(SmsClient::class, function ($app) {
            return new SmsClient(
                http:    $app->make(Http::class),
                apiBase: (string) config('acme.notifications-sms.api_base', 'https://api.twilio.com/2010-04-01'),
                sid:     (string) config('acme.notifications-sms.sid', ''),
                token:   (string) config('acme.notifications-sms.token', ''),
            );
        });

        $this->app->singleton(SmsChannel::class, function ($app) {
            return new SmsChannel($app->make(SmsClient::class), $app->make(LoggerInterface::class));
        });
    }

    protected function packageBoot(): void
    {
        $this->app->make(ChannelRegistry::class)->register($this->app->make(SmsChannel::class));
    }
}
