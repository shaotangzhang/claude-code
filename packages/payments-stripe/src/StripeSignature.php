<?php

declare(strict_types=1);

namespace Acme\PaymentsStripe;

use RuntimeException;

/**
 * Verifies the Stripe-Signature header against the raw body using the
 * webhook signing secret. Implements the same scheme as stripe-php:
 *   header = "t=<unix>,v1=<hmac_sha256(t.body, secret)>,v1=...,v0=..."
 *
 * We compare the v1 entries in constant time; tolerance guards against
 * replays. Throws RuntimeException on any failure.
 */
final class StripeSignature
{
    public static function verify(
        string $rawBody,
        string $header,
        string $secret,
        int $toleranceSeconds = 300,
        ?int $now = null,
    ): void {
        $now      ??= time();
        $parts    = self::parse($header);
        $timestamp = $parts['t']  ?? throw new RuntimeException('Stripe signature: missing timestamp');
        $sigs      = $parts['v1'] ?? [];

        if (! $sigs) {
            throw new RuntimeException('Stripe signature: no v1 signature present');
        }
        if (abs($now - (int) $timestamp) > $toleranceSeconds) {
            throw new RuntimeException('Stripe signature: timestamp outside tolerance');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        foreach ($sigs as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return;
            }
        }

        throw new RuntimeException('Stripe signature: no matching v1 entry');
    }

    /**
     * @return array{t?: string, v1?: list<string>, v0?: list<string>}
     */
    private static function parse(string $header): array
    {
        $out = [];
        foreach (explode(',', $header) as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) !== 2) continue;
            [$k, $v] = $kv;
            $k = trim($k); $v = trim($v);
            if ($k === 't') {
                $out['t'] = $v;
            } elseif ($k === 'v1' || $k === 'v0') {
                $out[$k] = $out[$k] ?? [];
                $out[$k][] = $v;
            }
        }

        return $out;
    }
}
