<?php

declare(strict_types=1);

return [
    // Webhook URL prefix; the route is /<prefix>/webhook
    'route_prefix' => env('ACME_STRIPE_SUBS_PREFIX', 'payments/stripe-subs'),

    // Webhook signing secret (different endpoint, different secret in
    // your Stripe dashboard from the one-shot payments-stripe webhook).
    'webhook_secret'  => env('STRIPE_SUBS_WEBHOOK_SECRET'),

    // Optional return / cancel URLs used when redirecting users to
    // Stripe's Checkout for first-time subscription.
    'success_url' => env('STRIPE_SUBS_SUCCESS_URL'),
    'cancel_url'  => env('STRIPE_SUBS_CANCEL_URL'),
];
