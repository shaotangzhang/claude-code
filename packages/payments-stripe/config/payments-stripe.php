<?php

declare(strict_types=1);

return [
    'secret_key'      => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),

    // The signing secret printed in Stripe Dashboard → Webhooks → Endpoint.
    // Used to verify Stripe-Signature on incoming webhooks.
    'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),

    // Tolerance in seconds for the timestamp inside Stripe-Signature.
    'timestamp_tolerance' => 300,

    // Where Stripe redirects the browser after a successful checkout session.
    'success_url' => env('STRIPE_SUCCESS_URL'),
    'cancel_url'  => env('STRIPE_CANCEL_URL'),

    'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com/v1'),
];
