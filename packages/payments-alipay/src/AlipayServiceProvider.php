<?php

declare(strict_types=1);

namespace Acme\PaymentsAlipay;

use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;

final class AlipayServiceProvider extends PackageServiceProvider
{
    protected string $key = 'payments-alipay';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(AlipayClient::class, function ($app) {
            return new AlipayClient(
                http:           $app->make(Http::class),
                appId:          (string) config('acme.payments-alipay.app_id', ''),
                privateKeyPem:  $this->resolveKey((string) config('acme.payments-alipay.app_private_key', '')),
                mode:           (string) config('acme.payments-alipay.mode', 'sandbox'),
            );
        });

        $this->app->singleton(AlipayGateway::class);
    }

    protected function packageBoot(): void
    {
        $this->app->make(GatewayRegistry::class)->register($this->app->make(AlipayGateway::class));
    }

    /** Accept either a PEM body or a file path. */
    private function resolveKey(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (is_file($value)) {
            return (string) file_get_contents($value);
        }

        return $value;
    }
}
