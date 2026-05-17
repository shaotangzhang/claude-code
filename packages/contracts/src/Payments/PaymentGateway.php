<?php

declare(strict_types=1);

namespace Acme\Contracts\Payments;

interface PaymentGateway
{
    /** Stable key used in URLs and the registry, e.g. "manual", "stripe", "alipay". */
    public function key(): string;

    /** Initiate a charge. Returns redirect URL, client secret, or pending status. */
    public function createIntent(PaymentIntent $intent): PaymentResult;

    /**
     * Parse a webhook callback into a normalized outcome.
     *
     * @param  array<string,mixed>  $payload  request body parsed
     * @param  array<string,string>  $headers
     * @return array{transaction_id:string,status:string,reference:?string,raw:array}
     *
     * Implementations verify signatures and throw on invalid payload.
     */
    public function parseWebhook(array $payload, array $headers): array;

    /** Optionally refund. Throws if gateway doesn't support refunds. */
    public function refund(string $gatewayReference, int $amountCents, string $currency): PaymentResult;
}
