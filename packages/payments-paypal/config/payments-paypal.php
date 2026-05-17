<?php

declare(strict_types=1);

return [
    'client_id'     => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),

    // 'sandbox' or 'live'.
    'mode' => env('PAYPAL_MODE', 'sandbox'),

    // Webhook id from PayPal Developer → Apps → Sandbox/Live webhook config.
    // Required for webhook verification.
    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

    // Hosted approval flow URLs.
    'return_url' => env('PAYPAL_RETURN_URL'),
    'cancel_url' => env('PAYPAL_CANCEL_URL'),
];
