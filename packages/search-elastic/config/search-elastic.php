<?php

declare(strict_types=1);

return [
    'host'          => env('ELASTIC_HOST', 'http://127.0.0.1:9200'),
    'api_key'       => env('ELASTIC_API_KEY'),
    'username'      => env('ELASTIC_USERNAME'),
    'password'      => env('ELASTIC_PASSWORD'),
    'index_prefix'  => env('ELASTIC_INDEX_PREFIX', 'acme_products'),
    'timeout_seconds' => 5,
];
