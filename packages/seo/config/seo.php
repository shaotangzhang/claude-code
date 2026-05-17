<?php

declare(strict_types=1);

return [
    'sitemap' => [
        'cache_ttl_seconds' => (int) env('ACME_SEO_SITEMAP_TTL', 600),
        'max_urls_per_file' => 50_000,
    ],
];
