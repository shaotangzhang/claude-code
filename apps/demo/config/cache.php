<?php

declare(strict_types=1);

return [
    'default' => env('CACHE_STORE', 'database'),

    'stores' => [
        'array'    => ['driver' => 'array', 'serialize' => false],
        'database' => ['driver' => 'database', 'connection' => env('DB_CACHE_CONNECTION'), 'table' => env('DB_CACHE_TABLE', 'cache')],
        'file'     => ['driver' => 'file', 'path' => storage_path('framework/cache/data'), 'lock_path' => storage_path('framework/cache/data')],
    ],

    'prefix' => env('CACHE_PREFIX', 'acme_cache_'),
];
