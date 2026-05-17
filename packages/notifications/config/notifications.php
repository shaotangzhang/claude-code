<?php

declare(strict_types=1);

return [
    // Channel registry — first listed is default if no preference.
    'channels' => ['mail', 'log'],

    // Event type → enabled channels map. Hosts can override per-event.
    'events' => [
        'order.placed'    => ['mail'],
        'order.paid'      => ['mail'],
        'order.fulfilled' => ['mail'],
        'order.canceled'  => ['mail'],
        'return.requested' => ['mail'],
        'stock.low'        => ['log'],   // ops-facing, log by default
        'article.published' => ['mail'],
    ],

    'mail' => [
        'from'      => env('ACME_NOTIFY_MAIL_FROM',   env('MAIL_FROM_ADDRESS')),
        'from_name' => env('ACME_NOTIFY_MAIL_NAME',   env('MAIL_FROM_NAME', 'Acme')),
        // Where to send ops-targeted alerts (stock.low, etc).
        'ops_to'    => env('ACME_NOTIFY_OPS_EMAIL'),
    ],
];
