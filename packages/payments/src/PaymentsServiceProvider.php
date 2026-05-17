<?php

declare(strict_types=1);

namespace Acme\Payments;

use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Payments\Gateways\ManualGateway;
use Acme\Payments\Services\PaymentService;
use Acme\Starter\Support\PackageServiceProvider;

final class PaymentsServiceProvider extends PackageServiceProvider
{
    protected string $key = 'payments';

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
        $this->app->singleton(GatewayRegistry::class);
        $this->app->singleton(PaymentService::class);
    }

    protected function packageBoot(): void
    {
        // Self-register Manual as the default gateway. Real-gateway sub-packages
        // (acme/payments-stripe etc.) register themselves the same way.
        $this->app->make(GatewayRegistry::class)->register(new ManualGateway());
    }
}
