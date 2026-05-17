<?php

declare(strict_types=1);

namespace Acme\PaymentsStripe\Tests\Unit;

use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use Acme\PaymentsStripe\StripeClient;
use Acme\PaymentsStripe\StripeGateway;
use Orchestra\Testbench\TestCase;

final class StripeGatewayTest extends TestCase
{
    public function test_key_is_stripe(): void
    {
        $gw = new StripeGateway($this->fakeClient([]));
        $this->assertSame('stripe', $gw->key());
    }

    public function test_create_intent_returns_redirect_url(): void
    {
        $client = $this->fakeClient(['url' => 'https://checkout.stripe.com/abc', 'id' => 'cs_123', 'payment_intent' => 'pi_456']);
        $gw     = new StripeGateway($client);

        $result = $gw->createIntent(new PaymentIntent(
            transactionId: 'tx1', amountCents: 9900, currency: 'USD',
            relatedType: 'order', relatedId: 'o1',
            returnUrl: 'https://shop.test/return',
        ));

        $this->assertSame(PaymentResult::STATUS_PENDING, $result->status);
        $this->assertSame('https://checkout.stripe.com/abc', $result->redirectUrl);
        $this->assertSame('pi_456', $result->gatewayReference);
    }

    public function test_parse_webhook_succeeded(): void
    {
        $gw = new StripeGateway($this->fakeClient([]));
        $parsed = $gw->parseWebhook([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => [
                'id' => 'pi_42',
                'metadata' => ['transaction_id' => 'tx1', 'related_type' => 'order', 'related_id' => 'o1'],
            ]],
        ], []); // empty headers — secret missing means verification is skipped

        $this->assertSame('tx1', $parsed['transaction_id']);
        $this->assertSame('succeeded', $parsed['status']);
        $this->assertSame('pi_42', $parsed['reference']);
    }

    public function test_parse_webhook_failed(): void
    {
        $gw = new StripeGateway($this->fakeClient([]));
        $parsed = $gw->parseWebhook([
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => 'pi_42', 'metadata' => ['transaction_id' => 'tx9']]],
        ], []);
        $this->assertSame('failed', $parsed['status']);
    }

    private function fakeClient(array $response): StripeClient
    {
        return new class($response) extends StripeClient {
            public function __construct(private readonly array $resp)
            {
            }
            public function createCheckoutSession(array $payload): array { return $this->resp; }
            public function refund(string $paymentIntentId, int $amountCents, string $currency): array { return $this->resp; }
            public function retrievePaymentIntent(string $id): array { return $this->resp; }
        };
    }
}
