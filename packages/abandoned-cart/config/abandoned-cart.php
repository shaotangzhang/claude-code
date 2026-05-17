<?php

declare(strict_types=1);

return [
    // Inactivity threshold (hours) after which an active cart is first
    // marked abandoned.
    'idle_hours' => (int) env('ACME_CART_ABANDON_HOURS', 24),

    'min_items'   => 1,
    'batch_limit' => (int) env('ACME_CART_ABANDON_BATCH', 200),

    // Recovery link TTL (hours). After this the controller refuses.
    'token_ttl_hours' => (int) env('ACME_CART_ABANDON_TTL', 72),

    'route_prefix' => env('ACME_CART_ABANDON_PREFIX', 'cart'),

    // Default channels for the `cart.abandoned` notification event.
    'default_channels' => ['mail'],

    /*
     * Multi-round reminders.
     *
     * Round 1 fires immediately on mark (hours_after_mark = 0).
     * Each subsequent round fires when (now - abandoned_at) ≥
     * hours_after_mark AND the previous round has been sent.
     *
     * Coupon (optional): when set, the tick generates a unique
     * Coupon row from the template and includes the code in the body.
     *
     * Defaults: gentle nudge → 10% off → 15% off final-chance.
     */
    'rounds' => [
        1 => [
            'hours_after_mark' => 0,
            'coupon' => null,
        ],
        2 => [
            'hours_after_mark' => 48,
            'coupon' => [
                'prefix'       => 'COMEBACK_',
                'type'         => 'percent',   // 'percent' | 'fixed'
                'value'        => 10,
                'min_subtotal' => null,
                'ttl_hours'    => 14 * 24,
            ],
        ],
        3 => [
            'hours_after_mark' => 144,
            'coupon' => [
                'prefix'       => 'LASTCHANCE_',
                'type'         => 'percent',
                'value'        => 15,
                'min_subtotal' => null,
                'ttl_hours'    => 7 * 24,
            ],
        ],
    ],
];
