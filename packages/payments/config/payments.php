<?php

declare(strict_types=1);

return [
    'route_prefix' => env('ACME_PAYMENTS_PREFIX', 'payments'),

    // Default gateway for new intents when no explicit key is provided.
    'default_gateway' => env('ACME_PAYMENTS_DEFAULT', 'manual'),

    'manual' => [
        // When true, anyone with the `payments.manual.confirm` capability
        // can mark a manual transaction as succeeded via the admin route.
        'admin_confirm_enabled' => true,
    ],

    // Webhook signing secret per gateway (gateway impls read this themselves)
    'secrets' => [
        // 'stripe' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
