<?php

declare(strict_types=1);

return [
    'brand'  => env('ACME_ADMIN_BRAND', 'Acme'),
    'prefix' => env('ACME_ADMIN_PREFIX', 'admin'),

    // Capability required to even enter /admin.
    'entry_capability' => env('ACME_ADMIN_ENTRY_CAPABILITY', null),
];
