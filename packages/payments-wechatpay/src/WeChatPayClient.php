<?php

declare(strict_types=1);

namespace Acme\PaymentsWeChatPay;

use Illuminate\Http\Client\Factory as Http;
use RuntimeException;

/**
 * WeChat Pay v3 HTTP client. Each request is RSA-SHA256-signed with the
 * merchant's API private key (per WECHATPAY2-SHA256-RSA2048 scheme).
 */
class WeChatPayClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $apiBase,
        private readonly string $mchId,
        private readonly string $appId,
        private readonly string $serialNo,
        private readonly string $privateKeyPem,
    ) {}

    /** Native (QR-code) checkout. Returns code_url to render as a QR image. */
    public function createNativeOrder(array $body): array
    {
        $body['mchid']  = $this->mchId;
        $body['appid']  = $this->appId;
        $jsonBody       = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $path           = '/v3/pay/transactions/native';

        return $this->post($path, (string) $jsonBody);
    }

    public function queryByOutTradeNo(string $outTradeNo): array
    {
        $path = "/v3/pay/transactions/out-trade-no/{$outTradeNo}?mchid={$this->mchId}";

        return $this->get($path);
    }

    public function refund(string $outTradeNo, int $amountCents, string $currency): array
    {
        $body = [
            'out_trade_no'  => $outTradeNo,
            'out_refund_no' => $outTradeNo . '-r-' . dechex(time()),
            'amount'        => ['refund' => $amountCents, 'total' => $amountCents, 'currency' => strtoupper($currency)],
        ];

        return $this->post('/v3/refund/domestic/refunds', json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function get(string $path): array
    {
        $auth = WeChatPaySignature::signRequest('GET', $path, '', $this->privateKeyPem, $this->mchId, $this->serialNo);

        return $this->http->baseUrl($this->apiBase)
            ->withHeaders(['Authorization' => $auth])
            ->acceptJson()
            ->get($path)
            ->throw()->json() ?? [];
    }

    private function post(string $path, string $body): array
    {
        $auth = WeChatPaySignature::signRequest('POST', $path, $body, $this->privateKeyPem, $this->mchId, $this->serialNo);

        return $this->http->baseUrl($this->apiBase)
            ->withHeaders(['Authorization' => $auth, 'Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->withBody($body, 'application/json')
            ->post($path)
            ->throw()->json() ?? [];
    }
}
