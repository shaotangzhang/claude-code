<?php

declare(strict_types=1);

namespace Acme\PaymentsStripe;

use Acme\Contracts\Payments\PaymentGateway;
use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use RuntimeException;

final class StripeGateway implements PaymentGateway
{
    public function __construct(private readonly StripeClient $client) {}

    public function key(): string { return 'stripe'; }

    public function createIntent(PaymentIntent $intent): PaymentResult
    {
        $successUrl = (string) ($intent->returnUrl ?? config('acme.payments-stripe.success_url'));
        $cancelUrl  = (string) config('acme.payments-stripe.cancel_url');

        // Hosted Checkout — the simplest integration; redirects user.
        $session = $this->client->createCheckoutSession([
            'mode'                                 => 'payment',
            'success_url'                          => $successUrl,
            'cancel_url'                           => $cancelUrl,
            'client_reference_id'                  => $intent->transactionId,
            'line_items[0][quantity]'              => 1,
            'line_items[0][price_data][currency]'  => strtolower($intent->currency),
            'line_items[0][price_data][unit_amount]' => $intent->amountCents,
            'line_items[0][price_data][product_data][name]' => $intent->description ?? "Tx {$intent->transactionId}",
            'metadata[transaction_id]'             => $intent->transactionId,
            'metadata[related_type]'               => $intent->relatedType,
            'metadata[related_id]'                 => $intent->relatedId,
        ]);

        return new PaymentResult(
            status:           PaymentResult::STATUS_PENDING,
            redirectUrl:      (string) ($session['url'] ?? ''),
            gatewayReference: (string) ($session['payment_intent'] ?? $session['id'] ?? ''),
            raw:              $session,
        );
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        // Stripe sends the raw JSON body verbatim; we receive it pre-parsed
        // here. Signature verification needs the raw body though, which the
        // controller passes via $headers['__raw_body'] (set by WebhookController).
        $secret = (string) config('acme.payments-stripe.webhook_secret');
        $sigKey = array_change_key_case($headers, CASE_LOWER)['stripe-signature'] ?? null;
        $raw    = $headers['__raw_body'] ?? json_encode($payload);

        if ($secret && $sigKey) {
            $sig = is_array($sigKey) ? ($sigKey[0] ?? '') : $sigKey;
            StripeSignature::verify((string) $raw, (string) $sig, $secret,
                toleranceSeconds: (int) config('acme.payments-stripe.timestamp_tolerance', 300));
        }

        $type = (string) ($payload['type'] ?? '');
        $obj  = (array)  ($payload['data']['object'] ?? []);
        $meta = (array)  ($obj['metadata'] ?? []);
        $txId = (string) ($meta['transaction_id'] ?? $obj['client_reference_id'] ?? '');

        if ($txId === '') {
            throw new RuntimeException('Stripe webhook: cannot locate transaction_id in payload');
        }

        $status = match ($type) {
            'checkout.session.completed',
            'payment_intent.succeeded'  => 'succeeded',
            'payment_intent.payment_failed',
            'checkout.session.expired'  => 'failed',
            default                     => 'unknown',
        };

        return [
            'transaction_id' => $txId,
            'status'         => $status,
            'reference'      => $obj['payment_intent'] ?? $obj['id'] ?? null,
            'raw'            => ['type' => $type, 'object' => $obj],
        ];
    }

    public function refund(string $gatewayReference, int $amountCents, string $currency): PaymentResult
    {
        $resp = $this->client->refund($gatewayReference, $amountCents, $currency);

        return new PaymentResult(
            status:           ($resp['status'] ?? '') === 'succeeded' ? PaymentResult::STATUS_SUCCEEDED : PaymentResult::STATUS_PENDING,
            gatewayReference: (string) ($resp['id'] ?? ''),
            raw:              $resp,
        );
    }
}
