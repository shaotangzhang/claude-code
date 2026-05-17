<?php

declare(strict_types=1);

namespace Acme\Payments\Tests\Unit;

use Acme\Contracts\Payments\PaymentGateway;
use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use Acme\Payments\Gateways\GatewayRegistry;
use Acme\Payments\Gateways\ManualGateway;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GatewayRegistryTest extends TestCase
{
    public function test_registers_resolves_lists(): void
    {
        $reg = new GatewayRegistry();
        $reg->register(new ManualGateway());

        $this->assertTrue($reg->has('manual'));
        $this->assertSame(['manual'], array_keys($reg->all()));
        $this->assertInstanceOf(PaymentGateway::class, $reg->resolve('manual'));
    }

    public function test_unknown_gateway_throws(): void
    {
        $this->expectException(RuntimeException::class);
        (new GatewayRegistry())->resolve('stripe');
    }

    public function test_manual_gateway_create_intent_returns_pending(): void
    {
        $r = (new ManualGateway())->createIntent(new PaymentIntent(
            transactionId: 'tx1', amountCents: 1000, currency: 'USD',
            relatedType: 'order', relatedId: 'o1',
        ));

        $this->assertSame(PaymentResult::STATUS_PENDING, $r->status);
        $this->assertStringStartsWith('manual_', (string) $r->gatewayReference);
    }
}
