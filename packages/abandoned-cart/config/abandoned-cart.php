<?php

declare(strict_types=1);

return [
    // Inactivity threshold (hours) after which a non-empty active cart
    // is considered abandoned.
    'idle_hours' => (int) env('ACME_CART_ABANDON_HOURS', 24),

    // Only flag carts with at least this many items (filters out empty
    // ones — those don't need rescuing).
    'min_items' => 1,

    // Hard ceiling per tick run, protects against thundering-herd
    // notifications on the first run.
    'batch_limit' => (int) env('ACME_CART_ABANDON_BATCH', 200),

    // Recovery link TTL (hours). After this, the token still resolves
    // but the controller refuses the restore.
    'token_ttl_hours' => (int) env('ACME_CART_ABANDON_TTL', 72),

    // Notification routing — what notifications.events.cart.abandoned
    // resolves to if the host hasn't set it.
    'default_channels' => ['mail'],

    // Route prefix for the recovery link.
    'route_prefix' => env('ACME_CART_ABANDON_PREFIX', 'cart'),
];
