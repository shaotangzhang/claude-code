<?php

declare(strict_types=1);

return [
    'driver' => env('ACME_SEARCH_DRIVER', 'database'),

    'route_prefix' => env('ACME_SEARCH_PREFIX', 'search'),

    'per_page' => 20,

    // Database driver options (used when driver=database).
    'database' => [
        'min_query_length' => 2,
    ],
];
