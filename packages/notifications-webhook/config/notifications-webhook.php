<?php

declare(strict_types=1);

return [
    // Single global endpoint for v0.1. Multi-endpoint (per-event-type
    // URL + secret) is planned for 0.2 via a webhook_endpoints table.
    'url'    => env('ACME_WEBHOOK_URL'),
    'secret' => env('ACME_WEBHOOK_SECRET'),

    // Header that carries the signature. "X-Acme-Signature: t=<unix>,v1=<hmac>"
    'signature_header' => 'X-Acme-Signature',

    'timeout_seconds' => 5,
];
