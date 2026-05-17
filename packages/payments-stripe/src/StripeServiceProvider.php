<?php

declare(strict_types=1);

namespace Acme\PaymentsStripe;

use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;

final class StripeServiceProvider extends PackageServiceProvider
{
    protected string $key = 'payments-stripe';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            return new StripeClient(
                http:      $app->make(Http::class),
                secretKey: (string) config('acme.payments-stripe.secret_key', ''),
                apiBase:   (string) config('acme.payments-stripe.api_base', 'https://api.stripe.com/v1'),
            );
        });

        $this->app->singleton(StripeGateway::class);
    }

    protected function packageBoot(): void
    {
        // Self-register with the central gateway registry.
        $this->app->make(GatewayRegistry::class)->register($this->app->make(StripeGateway::class));
    }
}
