<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Services;

use Acme\Cart\Models\Coupon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Generates a one-shot Coupon from a recovery-round template.
 *
 * Template shape:
 *   {
 *     prefix:       'COMEBACK_',
 *     type:         'percent' | 'fixed',
 *     value:        10,
 *     min_subtotal: ?int,
 *     ttl_hours:    int
 *   }
 */
final class CouponMinter
{
    public function mint(string $currency, array $template): Coupon
    {
        $code = (string) ($template['prefix'] ?? 'CMB_')
              . strtoupper(substr((string) Str::ulid(), -8));

        return Coupon::create([
            'code'              => $code,
            'type'              => $template['type'] ?? Coupon::TYPE_PERCENT,
            'value'             => (int) ($template['value'] ?? 0),
            'currency'          => ($template['type'] ?? 'percent') === 'fixed' ? $currency : null,
            'min_subtotal_cents' => $template['min_subtotal'] ?? null,
            'max_uses'          => 1,
            'used_count'        => 0,
            'starts_at'         => CarbonImmutable::now(),
            'ends_at'           => CarbonImmutable::now()->addHours((int) ($template['ttl_hours'] ?? 168)),
            'active'            => true,
            'meta_json'         => ['source' => 'abandoned-cart-reminder'],
        ]);
    }
}
