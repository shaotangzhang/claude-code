<?php

declare(strict_types=1);

return [
    'route_prefix' => env('ACME_CART_PREFIX', 'cart'),

    // Cookie that carries the guest cart token across requests.
    'cookie' => [
        'name'      => 'acme_cart',
        'days'      => 30,
        'secure'    => true,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    // Default tax / shipping strategy. Override in a host project by
    // binding the contracts to your own implementations.
    'tax' => [
        'flat_rate_bps' => (int) env('ACME_CART_TAX_BPS', 0), // basis points (e.g. 2000 = 20%)
        'label'         => env('ACME_CART_TAX_LABEL', 'Tax'),
    ],

    'shipping' => [
        'flat_rate_cents'    => (int) env('ACME_CART_SHIPPING_CENTS', 0),
        'free_above_cents'   => (int) env('ACME_CART_FREE_SHIP_AT', 0), // 0 = never free
        'label'              => env('ACME_CART_SHIPPING_LABEL', 'Standard shipping'),
        'days_min'           => 3,
        'days_max'           => 7,

        // When true, the built-in flat option appears alongside any
        // methods registered by acme/shipping-* packages. Disable to
        // let installed methods own shipping options exclusively.
        'builtin_flat_enabled' => env('ACME_CART_BUILTIN_SHIPPING', true),
    ],

    'max_quantity_per_line' => 999,
];
