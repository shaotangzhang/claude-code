<?php

declare(strict_types=1);

namespace Acme\PaymentsStripe;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

/**
 * Thin REST wrapper around Stripe's API. Centralized here so the gateway
 * stays pure logic + tests can swap in a faked HTTP client.
 *
 * Endpoints used (all v1):
 *   POST /checkout/sessions       — hosted checkout, returns a URL
 *   POST /refunds                 — refund a payment_intent
 *   GET  /payment_intents/{id}    — verify state on demand
 */
class StripeClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $secretKey,
        private readonly string $apiBase,
    ) {}

    public function createCheckoutSession(array $payload): array
    {
        return $this->request()->asForm()->post('/checkout/sessions', $payload)->throw()->json() ?? [];
    }

    public function refund(string $paymentIntentId, int $amountCents, string $currency): array
    {
        return $this->request()->asForm()->post('/refunds', [
            'payment_intent' => $paymentIntentId,
            'amount'         => $amountCents,
            'currency'       => strtolower($currency),
        ])->throw()->json() ?? [];
    }

    public function retrievePaymentIntent(string $id): array
    {
        return $this->request()->get("/payment_intents/{$id}")->throw()->json() ?? [];
    }

    private function request(): PendingRequest
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('Stripe secret key not configured.');
        }

        return $this->http->baseUrl($this->apiBase)
            ->withToken($this->secretKey)
            ->acceptJson();
    }
}
