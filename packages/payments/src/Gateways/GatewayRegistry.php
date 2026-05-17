<?php

declare(strict_types=1);

namespace Acme\Payments\Gateways;

use Acme\Contracts\Payments\PaymentGateway;
use RuntimeException;

/**
 * Each gateway implementation registers itself by key. Lookup happens at
 * intent-creation time and again on each webhook (URL contains key).
 */
final class GatewayRegistry
{
    /** @var array<string,PaymentGateway> */
    private array $gateways = [];

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->key()] = $gateway;
    }

    public function resolve(string $key): PaymentGateway
    {
        return $this->gateways[$key] ?? throw new RuntimeException("Unknown payment gateway: {$key}");
    }

    public function has(string $key): bool
    {
        return isset($this->gateways[$key]);
    }

    /** @return array<string,PaymentGateway> */
    public function all(): array
    {
        return $this->gateways;
    }
}
