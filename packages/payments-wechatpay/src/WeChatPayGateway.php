<?php

declare(strict_types=1);

namespace Acme\PaymentsWeChatPay;

use Acme\Contracts\Payments\PaymentGateway;
use Acme\Contracts\Payments\PaymentIntent;
use Acme\Contracts\Payments\PaymentResult;
use RuntimeException;

final class WeChatPayGateway implements PaymentGateway
{
    public function __construct(private readonly WeChatPayClient $client) {}

    public function key(): string { return 'wechatpay'; }

    public function createIntent(PaymentIntent $intent): PaymentResult
    {
        $body = [
            'out_trade_no' => $intent->transactionId,
            'description'  => $intent->description ?? "Tx {$intent->transactionId}",
            'notify_url'   => (string) config('acme.payments-wechatpay.notify_url', ''),
            'amount' => [
                'total'    => $intent->amountCents,
                'currency' => strtoupper($intent->currency),
            ],
            'attach' => json_encode([
                'transaction_id' => $intent->transactionId,
                'related_type'   => $intent->relatedType,
                'related_id'     => $intent->relatedId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $resp = $this->client->createNativeOrder($body);

        return new PaymentResult(
            status:           PaymentResult::STATUS_PENDING,
            redirectUrl:      (string) ($resp['code_url'] ?? ''),    // QR-encoded URL
            gatewayReference: $intent->transactionId,
            raw:              $resp,
        );
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        $h = array_change_key_case($headers, CASE_LOWER);
        $timestamp = (string) ($h['wechatpay-timestamp'][0] ?? $h['wechatpay-timestamp'] ?? '');
        $nonce     = (string) ($h['wechatpay-nonce'][0]     ?? $h['wechatpay-nonce']     ?? '');
        $signature = (string) ($h['wechatpay-signature'][0] ?? $h['wechatpay-signature'] ?? '');
        $raw       = (string) ($headers['__raw_body'] ?? json_encode($payload));

        $publicKey = (string) config('acme.payments-wechatpay.platform_public_key', '');
        $apiKey    = (string) config('acme.payments-wechatpay.apiv3_key', '');

        if ($publicKey !== '') {
            if (! WeChatPaySignature::verifyWebhook($timestamp, $nonce, $raw, $signature, $publicKey)) {
                throw new RuntimeException('WeChatPay webhook: signature verification failed.');
            }
        }

        // Decrypt resource if present + APIv3 key configured.
        $resource = $payload['resource'] ?? null;
        if (is_array($resource) && $apiKey !== '') {
            $plain = WeChatPaySignature::decryptResource(
                (string) ($resource['ciphertext'] ?? ''),
                (string) ($resource['associated_data'] ?? ''),
                (string) ($resource['nonce'] ?? ''),
                $apiKey,
            );
            $decoded = json_decode($plain, true) ?: [];
        } else {
            $decoded = (array) $resource;
        }

        $attach = $decoded['attach'] ?? null;
        $meta = is_string($attach) ? (json_decode($attach, true) ?: []) : [];
        $txId = (string) ($meta['transaction_id'] ?? $decoded['out_trade_no'] ?? '');
        if ($txId === '') {
            throw new RuntimeException('WeChatPay webhook: cannot locate transaction id.');
        }

        $tradeState = (string) ($decoded['trade_state'] ?? '');
        $status = match ($tradeState) {
            'SUCCESS'                    => 'succeeded',
            'CLOSED', 'PAYERROR', 'REVOKED' => 'failed',
            default                      => 'unknown',
        };

        return [
            'transaction_id' => $txId,
            'status'         => $status,
            'reference'      => $decoded['transaction_id'] ?? null,  // wechat-side id
            'raw'            => $decoded,
        ];
    }

    public function refund(string $gatewayReference, int $amountCents, string $currency): PaymentResult
    {
        $resp = $this->client->refund($gatewayReference, $amountCents, $currency);
        $state = (string) ($resp['status'] ?? '');

        return new PaymentResult(
            status:           in_array($state, ['SUCCESS', 'PROCESSING'], true) ? PaymentResult::STATUS_SUCCEEDED : PaymentResult::STATUS_PENDING,
            gatewayReference: (string) ($resp['refund_id'] ?? $gatewayReference),
            raw:              $resp,
        );
    }
}
