<?php

declare(strict_types=1);

return [
    'route_prefix' => env('ACME_CHECKOUT_PREFIX', 'checkout'),

    // Order number generator pattern. {y}{m}{d}-{ulid_short} by default.
    'order_number_prefix' => env('ACME_ORDER_PREFIX', ''),

    // Force-require login before /checkout. If false, guest checkout
    // is allowed (cart already supports guest mode).
    'require_login' => env('ACME_CHECKOUT_REQUIRE_LOGIN', true),
];
