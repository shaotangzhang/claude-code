<?php

declare(strict_types=1);

namespace Acme\PaymentsAlipay;

use RuntimeException;

/**
 * Alipay's RSA2 (SHA256withRSA) signing scheme.
 *
 *   payload string = sorted-by-key URL-encoded form  (excluding sign + sign_type)
 *   signature      = base64( RSA-SHA256( payload, app_private_key ) )
 *
 * Verification (notify webhook):
 *   take raw POST body's k=v pairs, drop sign + sign_type, sort, join
 *   verify against Alipay's public key.
 */
final class AlipaySignature
{
    public static function sign(array $params, string $privateKeyPem): string
    {
        $payload = self::canonicalString($params);

        $key = openssl_pkey_get_private($privateKeyPem);
        if ($key === false) {
            throw new RuntimeException('Alipay: failed to load app private key.');
        }

        $sig = '';
        $ok  = openssl_sign($payload, $sig, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Alipay: openssl_sign failed.');
        }

        return base64_encode($sig);
    }

    public static function verify(array $params, string $signature, string $alipayPublicKeyPem): bool
    {
        $payload = self::canonicalString($params);

        $key = openssl_pkey_get_public($alipayPublicKeyPem);
        if ($key === false) {
            throw new RuntimeException('Alipay: failed to load alipay public key.');
        }

        return openssl_verify($payload, base64_decode($signature), $key, OPENSSL_ALGO_SHA256) === 1;
    }

    /** Sorted key=value joined by '&', excluding sign + sign_type and empty values. */
    public static function canonicalString(array $params): string
    {
        $filtered = [];
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $k === 'sign_type') continue;
            if ($v === null || $v === '')           continue;
            $filtered[$k] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $v;
        }
        ksort($filtered);

        $pairs = [];
        foreach ($filtered as $k => $v) {
            $pairs[] = "{$k}={$v}";
        }

        return implode('&', $pairs);
    }
}
