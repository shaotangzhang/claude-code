<?php

declare(strict_types=1);

namespace Acme\PaymentsAlipay;

use Illuminate\Http\Client\Factory as Http;
use RuntimeException;

/**
 * Alipay Open Platform v1 gateway client. Every request is a
 * URL-encoded form POST to /gateway.do with a top-level method dispatch.
 *
 * Endpoints we use:
 *   - alipay.trade.page.pay      → Wap/PC page (returns html / redirect URL)
 *   - alipay.trade.precreate     → QR-code scan path
 *   - alipay.trade.query
 *   - alipay.trade.refund
 */
class AlipayClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $appId,
        private readonly string $privateKeyPem,
        private readonly string $mode,
    ) {}

    public function baseUrl(): string
    {
        return $this->mode === 'live'
            ? 'https://openapi.alipay.com'
            : 'https://openapi-sandbox.dl.alipaydev.com';
    }

    /** PC-page checkout — returns redirect URL or HTML form. */
    public function pagePay(array $bizContent, ?string $returnUrl, ?string $notifyUrl): string
    {
        $params = $this->buildParams('alipay.trade.page.pay', $bizContent, $returnUrl, $notifyUrl);
        // page.pay returns a 302 / form to be rendered; for simplicity we
        // build a GET URL that browsers can follow.
        return $this->baseUrl() . '/gateway.do?' . http_build_query($params);
    }

    /** @return array{transaction_id?:string, sign?:string} */
    public function query(string $outTradeNo): array
    {
        return $this->invoke('alipay.trade.query', ['out_trade_no' => $outTradeNo]);
    }

    public function refund(string $outTradeNo, int $amountCents, string $currency): array
    {
        return $this->invoke('alipay.trade.refund', [
            'out_trade_no'    => $outTradeNo,
            'refund_amount'   => number_format($amountCents / 100, 2, '.', ''),
            'out_request_no'  => $outTradeNo . '-r-' . dechex(time()),
        ]);
    }

    private function invoke(string $method, array $bizContent): array
    {
        $params = $this->buildParams($method, $bizContent, null, null);

        $resp = $this->http->baseUrl($this->baseUrl())
            ->asForm()->acceptJson()
            ->post('/gateway.do', $params)
            ->throw()->json() ?? [];

        // Alipay wraps the response in <method_underscored>_response
        $key = str_replace('.', '_', $method) . '_response';

        return (array) ($resp[$key] ?? $resp);
    }

    /** @return array<string,string> */
    private function buildParams(string $method, array $bizContent, ?string $returnUrl, ?string $notifyUrl): array
    {
        if ($this->appId === '') {
            throw new RuntimeException('Alipay app_id not configured.');
        }
        if ($this->privateKeyPem === '') {
            throw new RuntimeException('Alipay app_private_key not configured.');
        }

        $params = [
            'app_id'      => $this->appId,
            'method'      => $method,
            'format'      => 'JSON',
            'charset'     => (string) config('acme.payments-alipay.charset', 'utf-8'),
            'sign_type'   => (string) config('acme.payments-alipay.sign_type', 'RSA2'),
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => (string) config('acme.payments-alipay.version', '1.0'),
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        if ($returnUrl) { $params['return_url'] = $returnUrl; }
        if ($notifyUrl) { $params['notify_url'] = $notifyUrl; }

        $params['sign'] = AlipaySignature::sign($params, $this->privateKeyPem);

        return $params;
    }
}
