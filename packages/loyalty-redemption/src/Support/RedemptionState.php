<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption\Support;

use Acme\Cart\Models\Cart;

/**
 * Tiny value-object accessor over cart.meta_json['loyalty_redemption'].
 * We don't introduce a new table — redemption is a per-cart ephemeral
 * setting that converts to a real LoyaltyTransaction at order time.
 */
final class RedemptionState
{
    public static function get(Cart $cart): ?array
    {
        $meta = (array) ($cart->meta_json ?? []);
        $r    = $meta['loyalty_redemption'] ?? null;

        if (! is_array($r) || ! isset($r['points'], $r['amount_cents'])) {
            return null;
        }

        return [
            'points'       => (int) $r['points'],
            'amount_cents' => (int) $r['amount_cents'],
            'currency'     => (string) ($r['currency'] ?? $cart->currency),
        ];
    }

    public static function set(Cart $cart, int $points, int $amountCents): void
    {
        $meta = (array) ($cart->meta_json ?? []);
        $meta['loyalty_redemption'] = [
            'points'       => $points,
            'amount_cents' => $amountCents,
            'currency'     => $cart->currency,
        ];
        $cart->meta_json = $meta;
        $cart->save();
    }

    public static function clear(Cart $cart): void
    {
        $meta = (array) ($cart->meta_json ?? []);
        unset($meta['loyalty_redemption']);
        $cart->meta_json = $meta ?: null;
        $cart->save();
    }
}
