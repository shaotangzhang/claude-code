<?php

declare(strict_types=1);

namespace Acme\PaymentsPayPal;

use Acme\Contracts\Payments\PaymentGateway;
use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use RuntimeException;

final class PayPalGateway implements PaymentGateway
{
    public function __construct(private readonly PayPalClient $client) {}

    public function key(): string { return 'paypal'; }

    public function createIntent(PaymentIntent $intent): PaymentResult
    {
        $returnUrl = (string) ($intent->returnUrl ?? config('acme.payments-paypal.return_url'));
        $cancelUrl = (string) config('acme.payments-paypal.cancel_url');

        $order = $this->client->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $intent->transactionId,
                'custom_id'    => $intent->transactionId,
                'description'  => $intent->description ?? "Tx {$intent->transactionId}",
                'amount' => [
                    'currency_code' => strtoupper($intent->currency),
                    'value'         => number_format($intent->amountCents / 100, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
            ],
        ]);

        $approveUrl = '';
        foreach ((array) ($order['links'] ?? []) as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approveUrl = (string) ($link['href'] ?? '');
                break;
            }
        }

        return new PaymentResult(
            status:           PaymentResult::STATUS_PENDING,
            redirectUrl:      $approveUrl,
            gatewayReference: (string) ($order['id'] ?? ''),
            raw:              $order,
        );
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        $webhookId = (string) config('acme.payments-paypal.webhook_id', '');
        if ($webhookId !== '') {
            if (! $this->client->verifyWebhookSignature($headers, $payload, $webhookId)) {
                throw new RuntimeException('PayPal webhook: signature verification failed.');
            }
        }

        $type     = (string) ($payload['event_type'] ?? '');
        $resource = (array)  ($payload['resource']   ?? []);

        // Locate the transaction id we stamped in custom_id / reference_id.
        $txId = (string) ($resource['custom_id'] ?? '');
        if ($txId === '') {
            $pu = $resource['purchase_units'][0] ?? [];
            $txId = (string) ($pu['custom_id'] ?? $pu['reference_id'] ?? '');
        }
        if ($txId === '') {
            throw new RuntimeException('PayPal webhook: cannot locate transaction id in payload');
        }

        $status = match ($type) {
            'CHECKOUT.ORDER.APPROVED',
            'PAYMENT.CAPTURE.COMPLETED' => 'succeeded',
            'CHECKOUT.ORDER.VOIDED',
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.DECLINED'  => 'failed',
            default                     => 'unknown',
        };

        return [
            'transaction_id' => $txId,
            'status'         => $status,
            'reference'      => $resource['id'] ?? null,
            'raw'            => ['event_type' => $type, 'resource' => $resource],
        ];
    }

    public function refund(string $gatewayReference, int $amountCents, string $currency): PaymentResult
    {
        $resp = $this->client->refund($gatewayReference, $amountCents, $currency);

        return new PaymentResult(
            status:           ($resp['status'] ?? '') === 'COMPLETED' ? PaymentResult::STATUS_SUCCEEDED : PaymentResult::STATUS_PENDING,
            gatewayReference: (string) ($resp['id'] ?? ''),
            raw:              $resp,
        );
    }
}
