<?php

declare(strict_types=1);

return [
    'two_factor' => [
        'enabled' => env('ACME_AUTH_2FA_ENABLED', false),
        'issuer'  => env('ACME_AUTH_2FA_ISSUER', 'Acme'),
    ],

    'sso' => [
        'providers' => [],
    ],

    'invitations' => [
        'ttl_hours' => 72,
    ],

    'session_log' => [
        'retain_days' => 90,
    ],
];
