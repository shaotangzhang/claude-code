<?php

declare(strict_types=1);

return [
    'route_prefix' => env('ACME_CATALOG_PREFIX', 'catalog'),

    'currency' => [
        'default'        => env('ACME_CATALOG_CURRENCY', 'USD'),
        'minor_unit'     => 2,
        'symbol'         => env('ACME_CATALOG_CURRENCY_SYMBOL', '$'),
        'symbol_position' => 'left',
    ],

    'grid_per_page' => 24,
];
