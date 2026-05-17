<?php

declare(strict_types=1);

namespace Acme\PaymentsPayPal\Tests\Unit;

use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use Acme\PaymentsPayPal\PayPalClient;
use Acme\PaymentsPayPal\PayPalGateway;
use Orchestra\Testbench\TestCase;
use RuntimeException;

final class PayPalGatewayTest extends TestCase
{
    public function test_key_is_paypal(): void
    {
        $gw = new PayPalGateway($this->fakeClient(['order' => [], 'verify' => true]));
        $this->assertSame('paypal', $gw->key());
    }

    public function test_create_intent_extracts_approve_url(): void
    {
        $client = $this->fakeClient(['order' => [
            'id' => 'ORDER-1',
            'links' => [
                ['rel' => 'self',    'href' => 'https://api.sandbox.paypal.com/...'],
                ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1'],
            ],
        ], 'verify' => true]);

        $result = (new PayPalGateway($client))->createIntent(new PaymentIntent(
            transactionId: 'tx1', amountCents: 12345, currency: 'USD',
            relatedType: 'order', relatedId: 'o1',
        ));

        $this->assertSame(PaymentResult::STATUS_PENDING, $result->status);
        $this->assertSame('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1', $result->redirectUrl);
        $this->assertSame('ORDER-1', $result->gatewayReference);
    }

    public function test_parse_webhook_succeeded_uses_custom_id(): void
    {
        $gw = new PayPalGateway($this->fakeClient(['verify' => true]));

        $parsed = $gw->parseWebhook([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource'   => ['id' => 'CAP-9', 'custom_id' => 'tx1'],
        ], ['paypal-transmission-id' => ['abc']]);

        $this->assertSame('tx1', $parsed['transaction_id']);
        $this->assertSame('succeeded', $parsed['status']);
        $this->assertSame('CAP-9', $parsed['reference']);
    }

    public function test_parse_webhook_failed_signature_throws(): void
    {
        config()->set('acme.payments-paypal.webhook_id', 'WH-XYZ');
        $gw = new PayPalGateway($this->fakeClient(['verify' => false]));

        $this->expectException(RuntimeException::class);
        $gw->parseWebhook(['event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => ['custom_id' => 'tx1']], []);
    }

    public function test_parse_webhook_skips_verification_when_no_webhook_id(): void
    {
        config()->set('acme.payments-paypal.webhook_id', '');
        $gw = new PayPalGateway($this->fakeClient(['verify' => false]));

        $parsed = $gw->parseWebhook(['event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => ['custom_id' => 'tx9']], []);
        $this->assertSame('tx9', $parsed['transaction_id']);
    }

    private function fakeClient(array $resp): PayPalClient
    {
        return new class($resp) extends PayPalClient {
            public function __construct(private readonly array $r) {}
            public function token(): string { return 'fake-token'; }
            public function createOrder(array $payload): array { return $this->r['order'] ?? []; }
            public function captureOrder(string $orderId): array { return $this->r['capture'] ?? []; }
            public function refund(string $captureId, int $amountCents, string $currency): array { return $this->r['refund'] ?? []; }
            public function verifyWebhookSignature(array $headers, array $eventBody, string $webhookId): bool { return (bool) ($this->r['verify'] ?? true); }
        };
    }
}
