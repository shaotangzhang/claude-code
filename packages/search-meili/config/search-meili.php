<?php

declare(strict_types=1);

return [
    'host'    => env('MEILI_HOST', 'http://127.0.0.1:7700'),
    'api_key' => env('MEILI_API_KEY'),

    // Single global index per locale, e.g. acme_products_en / acme_products_zh.
    'index_prefix' => env('MEILI_INDEX_PREFIX', 'acme_products'),

    // Attributes the engine treats as filterable for facets.
    'filterable_attributes' => ['brand', 'category', 'min_price_cents', 'max_price_cents'],

    // Per-request timeout in seconds.
    'timeout_seconds' => 5,
];
