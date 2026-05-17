<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module Discovery
    |--------------------------------------------------------------------------
    | The starter scans every installed composer package's `extra.acme.module`
    | block to build the registry. You can pin a deny list / allow list here
    | if a host project needs to selectively disable a module without removing
    | it from composer.
    */
    'modules' => [
        'allow' => env('ACME_MODULES_ALLOW'),
        'deny'  => env('ACME_MODULES_DENY'),
    ],

    'id_strategy' => env('ACME_ID_STRATEGY', 'ulid'),

    'admin' => [
        'prefix' => env('ACME_ADMIN_PREFIX', 'admin'),
    ],

    'api' => [
        'prefix'  => env('ACME_API_PREFIX', 'api'),
        'version' => env('ACME_API_VERSION', 'v1'),
    ],
];
