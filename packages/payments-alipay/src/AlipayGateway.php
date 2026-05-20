<?php

declare(strict_types=1);

namespace Acme\PaymentsAlipay;

use Acme\Contracts\Payments\PaymentGateway;
use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use RuntimeException;

final class AlipayGateway implements PaymentGateway
{
    public function __construct(private readonly AlipayClient $client) {}

    public function key(): string { return 'alipay'; }

    public function createIntent(PaymentIntent $intent): PaymentResult
    {
        $bizContent = [
            'out_trade_no'  => $intent->transactionId,
            'product_code'  => 'FAST_INSTANT_TRADE_PAY',
            'total_amount'  => number_format($intent->amountCents / 100, 2, '.', ''),
            'subject'       => $intent->description ?? "Tx {$intent->transactionId}",
            'passback_params' => urlencode(json_encode([
                'transaction_id' => $intent->transactionId,
                'related_type'   => $intent->relatedType,
                'related_id'     => $intent->relatedId,
            ])),
        ];

        $redirect = $this->client->pagePay(
            $bizContent,
            $intent->returnUrl ?? config('acme.payments-alipay.return_url'),
            (string) config('acme.payments-alipay.notify_url'),
        );

        return new PaymentResult(
            status:           PaymentResult::STATUS_PENDING,
            redirectUrl:      $redirect,
            gatewayReference: $intent->transactionId,
        );
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        $sign = (string) ($payload['sign'] ?? '');
        $pubKey = (string) config('acme.payments-alipay.alipay_public_key', '');
        if ($pubKey === '') {
            throw new RuntimeException('Alipay public key not configured — refusing to accept webhook.');
        }
        if (! AlipaySignature::verify($payload, $sign, $pubKey)) {
            throw new RuntimeException('Alipay webhook: signature verification failed.');
        }

        // Decode our passback so we know which transaction it is.
        $pb = json_decode(urldecode((string) ($payload['passback_params'] ?? '')), true) ?: [];
        $txId = (string) ($pb['transaction_id'] ?? $payload['out_trade_no'] ?? '');
        if ($txId === '') {
            throw new RuntimeException('Alipay webhook: cannot locate transaction id.');
        }

        $tradeStatus = (string) ($payload['trade_status'] ?? '');
        $status = match ($tradeStatus) {
            'TRADE_SUCCESS', 'TRADE_FINISHED' => 'succeeded',
            'TRADE_CLOSED'                    => 'failed',
            default                           => 'unknown',
        };

        return [
            'transaction_id' => $txId,
            'status'         => $status,
            'reference'      => $payload['trade_no'] ?? null,
            'raw'            => $payload,
        ];
    }

    public function refund(string $gatewayReference, int $amountCents, string $currency): PaymentResult
    {
        $resp = $this->client->refund($gatewayReference, $amountCents, $currency);
        $code = (string) ($resp['code'] ?? '');

        return new PaymentResult(
            status:           $code === '10000' ? PaymentResult::STATUS_SUCCEEDED : PaymentResult::STATUS_PENDING,
            gatewayReference: (string) ($resp['trade_no'] ?? $gatewayReference),
            raw:              $resp,
        );
    }
}
