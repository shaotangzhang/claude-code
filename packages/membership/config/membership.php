<?php

declare(strict_types=1);

return [
    // Days of grace after current_period_end before a subscription is forced
    // to "expired". Within grace, the subscription stays in "past_due".
    'grace_days' => (int) env('ACME_MEMBERSHIP_GRACE_DAYS', 7),

    // When true, tick command auto-renews paid-up subscriptions (extends
    // period without waiting for an explicit PaymentReceived). Use only
    // for free/auto-renew tiers — leave false in production once a real
    // billing package is wired up.
    'auto_renew_no_payment' => env('ACME_MEMBERSHIP_AUTO_RENEW', false),

    'route_prefix' => env('ACME_MEMBERSHIP_PREFIX', 'membership'),

    // Map tier.key => rbac role.key. When a subscription becomes active
    // we grant the role; when it expires we revoke. Leave empty to skip
    // rbac coupling entirely.
    'tier_to_role' => [
        // 'gold' => 'gold-member',
    ],
];
