<?php

declare(strict_types=1);

namespace Acme\PaymentsWeChatPay;

use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Starter\Support\PackageServiceProvider;
use Illuminate\Http\Client\Factory as Http;

final class WeChatPayServiceProvider extends PackageServiceProvider
{
    protected string $key = 'payments-wechatpay';

    protected bool $hasMigrations = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->root = dirname(__DIR__);
    }

    protected function packageRegister(): void
    {
        $this->app->singleton(WeChatPayClient::class, function ($app) {
            return new WeChatPayClient(
                http:           $app->make(Http::class),
                apiBase:        'https://api.mch.weixin.qq.com',
                mchId:          (string) config('acme.payments-wechatpay.mch_id', ''),
                appId:          (string) config('acme.payments-wechatpay.app_id', ''),
                serialNo:       (string) config('acme.payments-wechatpay.serial_no', ''),
                privateKeyPem:  $this->resolveKey((string) config('acme.payments-wechatpay.private_key', '')),
            );
        });

        $this->app->singleton(WeChatPayGateway::class);
    }

    protected function packageBoot(): void
    {
        $this->app->make(GatewayRegistry::class)->register($this->app->make(WeChatPayGateway::class));
    }

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
