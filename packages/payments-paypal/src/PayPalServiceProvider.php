<?php

declare(strict_types=1);

namespace Acme\PaymentsPayPal;

use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;

final class PayPalServiceProvider extends PackageServiceProvider
{
    protected string $key = 'payments-paypal';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(PayPalClient::class, function ($app) {
            return new PayPalClient(
                http:         $app->make(Http::class),
                cache:        $app->make(Cache::class),
                clientId:     (string) config('acme.payments-paypal.client_id', ''),
                clientSecret: (string) config('acme.payments-paypal.client_secret', ''),
                mode:         (string) config('acme.payments-paypal.mode', 'sandbox'),
            );
        });

        $this->app->singleton(PayPalGateway::class);
    }

    protected function packageBoot(): void
    {
        $this->app->make(GatewayRegistry::class)->register($this->app->make(PayPalGateway::class));
    }
}
