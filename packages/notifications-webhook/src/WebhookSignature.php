<?php

declare(strict_types=1);

namespace Acme\NotificationsWebhook;

/**
 * Same signature scheme as acme/payments-stripe::StripeSignature so
 * receivers can reuse one verifier across both ingress (Stripe → us)
 * and egress (us → consumer) webhooks.
 *
 * Header: "t=<unix>,v1=<hmac_sha256(t.payload, secret)>"
 */
final class WebhookSignature
{
    public static function sign(string $payload, string $secret, ?int $now = null): string
    {
        $t   = $now ?? time();
        $sig = hash_hmac('sha256', $t . '.' . $payload, $secret);

        return "t={$t},v1={$sig}";
    }
}
