<?php

declare(strict_types=1);

namespace Acme\PaymentsWeChatPay;

use RuntimeException;

/**
 * WeChat Pay v3 signature helpers.
 *
 * Outbound request:
 *   sig_str = method + "\n" + url_path + "\n" + timestamp + "\n" + nonce + "\n" + body + "\n"
 *   signature = base64( RSA-SHA256( sig_str, merchant_private_key ) )
 *   header: Authorization: WECHATPAY2-SHA256-RSA2048
 *           mchid="...", nonce_str="...", timestamp="...", serial_no="...", signature="..."
 *
 * Inbound webhook:
 *   sig_str = Wechatpay-Timestamp + "\n" + Wechatpay-Nonce + "\n" + body + "\n"
 *   verify against platform public key, signature from Wechatpay-Signature header.
 *   then decrypt resource.ciphertext using APIv3 key (AEAD-AES-256-GCM).
 */
final class WeChatPaySignature
{
    public static function signRequest(
        string $method,
        string $urlPath,
        string $body,
        string $privateKeyPem,
        string $mchId,
        string $serialNo,
        ?int $now = null,
        ?string $nonce = null,
    ): string {
        $timestamp = $now ?? time();
        $nonce     = $nonce ?? bin2hex(random_bytes(16));
        $payload   = "{$method}\n{$urlPath}\n{$timestamp}\n{$nonce}\n{$body}\n";

        $key = openssl_pkey_get_private($privateKeyPem);
        if ($key === false) {
            throw new RuntimeException('WeChatPay: failed to load merchant private key.');
        }
        $sig = '';
        if (! openssl_sign($payload, $sig, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('WeChatPay: openssl_sign failed.');
        }
        $signature = base64_encode($sig);

        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $mchId, $nonce, $timestamp, $serialNo, $signature,
        );
    }

    public static function verifyWebhook(
        string $timestamp,
        string $nonce,
        string $rawBody,
        string $signatureB64,
        string $platformPublicKeyPem,
    ): bool {
        $payload = "{$timestamp}\n{$nonce}\n{$rawBody}\n";

        $key = openssl_pkey_get_public($platformPublicKeyPem);
        if ($key === false) {
            throw new RuntimeException('WeChatPay: failed to load platform public key.');
        }

        return openssl_verify($payload, base64_decode($signatureB64), $key, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Decrypt the resource.ciphertext from a webhook event.
     * Algorithm: AEAD-AES-256-GCM. associated_data is plaintext header.
     */
    public static function decryptResource(string $ciphertextB64, string $associated, string $nonce, string $apiV3Key): string
    {
        $ciphertext = base64_decode($ciphertextB64);
        if (strlen($ciphertext) <= 16) {
            throw new RuntimeException('WeChatPay: ciphertext too short.');
        }
        // GCM tag is the last 16 bytes per WeChat's spec.
        $cipher = substr($ciphertext, 0, -16);
        $tag    = substr($ciphertext, -16);

        $plain = openssl_decrypt(
            $cipher,
            'aes-256-gcm',
            $apiV3Key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $associated,
        );

        if ($plain === false) {
            throw new RuntimeException('WeChatPay: AEAD-AES-256-GCM decryption failed.');
        }

        return $plain;
    }
}
