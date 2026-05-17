<?php

declare(strict_types=1);

return [
    'theme' => [
        // override of currently active theme key; null = read from DB
        'force_active' => env('ACME_CMS_FORCE_THEME'),
    ],

    'cache' => [
        // page-level HTML cache TTL in seconds; 0 disables.
        'page_ttl'  => (int) env('ACME_CMS_PAGE_TTL', 0),
        // per-block fragment cache default TTL.
        'block_ttl' => (int) env('ACME_CMS_BLOCK_TTL', 0),
    ],

    'routing' => [
        // when true, the catch-all front-end route is mounted at "/{slug?}".
        'mount_catch_all' => env('ACME_CMS_MOUNT_CATCH_ALL', true),
    ],
];
