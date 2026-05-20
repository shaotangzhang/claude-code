<?php

declare(strict_types=1);

namespace Acme\PaymentsStripeSubscriptions;

use Acme\PaymentsStripeSubscriptions\Services\StripeSubscriptionLinker;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;

final class StripeSubscriptionsServiceProvider extends PackageServiceProvider
{
    protected string $key = 'payments-stripe-subscriptions';

    protected bool $hasRoutesWeb = true;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(StripeSubscriptionClient::class, function ($app) {
            return new StripeSubscriptionClient(
                http:      $app->make(Http::class),
                // Reuses the same secret key as payments-stripe.
                secretKey: (string) config('acme.payments-stripe.secret_key', ''),
                apiBase:   (string) config('acme.payments-stripe.api_base', 'https://api.stripe.com/v1'),
            );
        });

        $this->app->singleton(StripeSubscriptionLinker::class);
    }
}
