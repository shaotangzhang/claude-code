<?php

declare(strict_types=1);

namespace Acme\PaymentsStripeSubscriptions;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

/**
 * Minimal Stripe Subscriptions v1 wrapper. Same auth as
 * payments-stripe's StripeClient — we reuse the secret-key env so
 * one secret covers both packages.
 *
 * Endpoints:
 *   POST /v1/customers
 *   POST /v1/checkout/sessions             (mode=subscription)
 *   POST /v1/subscriptions/{id}            (update)
 *   DELETE /v1/subscriptions/{id}          (cancel)
 */
class StripeSubscriptionClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $secretKey,
        private readonly string $apiBase,
    ) {}

    public function createCustomer(array $payload): array
    {
        return $this->request()->asForm()->post('/customers', $payload)->throw()->json() ?? [];
    }

    /**
     * Hosted Checkout in subscription mode. Returns a session whose
     * `url` is the redirect the user follows.
     */
    public function createSubscriptionCheckoutSession(array $payload): array
    {
        return $this->request()->asForm()->post('/checkout/sessions', $payload)->throw()->json() ?? [];
    }

    public function cancelSubscription(string $subId, bool $atPeriodEnd = true): array
    {
        if ($atPeriodEnd) {
            return $this->request()->asForm()
                ->post("/subscriptions/{$subId}", ['cancel_at_period_end' => 'true'])
                ->throw()->json() ?? [];
        }

        return $this->request()->delete("/subscriptions/{$subId}")->throw()->json() ?? [];
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
