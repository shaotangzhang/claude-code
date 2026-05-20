<?php

declare(strict_types=1);

namespace Acme\PaymentsWeChatPay\Tests\Unit;

use Acme\PaymentsWeChatPay\WeChatPaySignature;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WeChatPaySignatureTest extends TestCase
{
    public function test_sign_request_header_shape(): void
    {
        $keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keypair, $priv);

        $header = WeChatPaySignature::signRequest(
            'POST', '/v3/pay/transactions/native', '{"a":1}',
            $priv, 'MCH123', 'SERIAL999',
            now: 1_700_000_000, nonce: 'NONCEXYZ',
        );

        $this->assertStringStartsWith('WECHATPAY2-SHA256-RSA2048 ', $header);
        $this->assertStringContainsString('mchid="MCH123"', $header);
        $this->assertStringContainsString('nonce_str="NONCEXYZ"', $header);
        $this->assertStringContainsString('timestamp="1700000000"', $header);
        $this->assertStringContainsString('serial_no="SERIAL999"', $header);
        $this->assertSame(1, preg_match('/signature="[A-Za-z0-9+\/=]+"/', $header));
    }

    public function test_webhook_signature_round_trip(): void
    {
        $keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keypair, $priv);
        $pub = openssl_pkey_get_details($keypair)['key'];

        $body = '{"event":"PAYMENT.SUCCESS"}';
        $ts   = '1700000000';
        $nce  = 'nonce-xyz';

        // Pretend we are Wechat: sign with our key, then verify with the
        // matching public key — simulates the same flow Wechat → us.
        $payload = "{$ts}\n{$nce}\n{$body}\n";
        openssl_sign($payload, $sig, $priv, OPENSSL_ALGO_SHA256);
        $sigB64 = base64_encode($sig);

        $this->assertTrue(WeChatPaySignature::verifyWebhook($ts, $nce, $body, $sigB64, $pub));

        // Tamper body → fail.
        $this->assertFalse(WeChatPaySignature::verifyWebhook($ts, $nce, '{"x":1}', $sigB64, $pub));
    }

    public function test_aead_decrypt_round_trip(): void
    {
        $key      = random_bytes(32);
        $nonce    = random_bytes(12);
        $assoc    = 'transaction';
        $plain    = json_encode(['out_trade_no' => 'tx-9', 'trade_state' => 'SUCCESS']);

        // Encrypt with the same algo to mimic Wechat's payload.
        $cipher   = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $assoc);
        $ciphertextB64 = base64_encode($cipher . $tag);

        $decrypted = WeChatPaySignature::decryptResource($ciphertextB64, $assoc, $nonce, $key);
        $this->assertSame($plain, $decrypted);
    }

    public function test_aead_decrypt_fails_on_wrong_key(): void
    {
        $key   = random_bytes(32);
        $nonce = random_bytes(12);
        $cipher = openssl_encrypt('hi', 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, 'a');
        $ct = base64_encode($cipher . $tag);

        $this->expectException(RuntimeException::class);
        WeChatPaySignature::decryptResource($ct, 'a', $nonce, random_bytes(32));
    }
}
