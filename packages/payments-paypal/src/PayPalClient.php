<?php

declare(strict_types=1);

namespace Acme\PaymentsPayPal;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

/**
 * Thin wrapper around PayPal Orders v2 + Payments API. Token is OAuth2
 * client_credentials, cached for slightly less than its returned TTL.
 *
 * Endpoints used:
 *   POST /v1/oauth2/token                       — fetch bearer
 *   POST /v2/checkout/orders                    — create order
 *   POST /v2/checkout/orders/{id}/capture       — capture (we let
 *                                                 PayPal capture on
 *                                                 approval automatically)
 *   POST /v2/payments/captures/{id}/refund      — refund
 *   POST /v1/notifications/verify-webhook-signature — verify webhook
 */
class PayPalClient
{
    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $mode,
    ) {}

    public function baseUrl(): string
    {
        return $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function token(): string
    {
        $key = "acme.paypal.token.{$this->mode}.{$this->clientId}";
        $hit = $this->cache->get($key);
        if (is_string($hit) && $hit !== '') {
            return $hit;
        }

        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('PayPal client_id / client_secret not configured.');
        }

        $resp = $this->http->baseUrl($this->baseUrl())
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->acceptJson()
            ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()->json() ?? [];

        $token = (string) ($resp['access_token'] ?? '');
        $ttl   = max(60, (int) ($resp['expires_in'] ?? 32400) - 60);
        $this->cache->put($key, $token, $ttl);

        return $token;
    }

    public function createOrder(array $payload): array
    {
        return $this->request()->post('/v2/checkout/orders', $payload)->throw()->json() ?? [];
    }

    public function captureOrder(string $orderId): array
    {
        return $this->request()->post("/v2/checkout/orders/{$orderId}/capture", [])->throw()->json() ?? [];
    }

    public function refund(string $captureId, int $amountCents, string $currency): array
    {
        return $this->request()->post("/v2/payments/captures/{$captureId}/refund", [
            'amount' => [
                'value'         => number_format($amountCents / 100, 2, '.', ''),
                'currency_code' => strtoupper($currency),
            ],
        ])->throw()->json() ?? [];
    }

    /** PayPal-hosted webhook signature verification (server-side). */
    public function verifyWebhookSignature(array $headers, array $eventBody, string $webhookId): bool
    {
        $h = array_change_key_case($headers, CASE_LOWER);
        $resp = $this->request()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo'         => $h['paypal-auth-algo'][0] ?? $h['paypal-auth-algo'] ?? '',
            'cert_url'          => $h['paypal-cert-url'][0]  ?? $h['paypal-cert-url']  ?? '',
            'transmission_id'   => $h['paypal-transmission-id'][0]   ?? $h['paypal-transmission-id']   ?? '',
            'transmission_sig'  => $h['paypal-transmission-sig'][0]  ?? $h['paypal-transmission-sig']  ?? '',
            'transmission_time' => $h['paypal-transmission-time'][0] ?? $h['paypal-transmission-time'] ?? '',
            'webhook_id'        => $webhookId,
            'webhook_event'     => $eventBody,
        ])->throw()->json() ?? [];

        return ($resp['verification_status'] ?? '') === 'SUCCESS';
    }

    private function request(): PendingRequest
    {
        return $this->http->baseUrl($this->baseUrl())
            ->withToken($this->token())
            ->acceptJson()
            ->asJson();
    }
}
