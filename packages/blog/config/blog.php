<?php

declare(strict_types=1);

return [
    'route_prefix' => env('ACME_BLOG_PREFIX', 'blog'),

    'rss' => [
        'cache_ttl_seconds' => (int) env('ACME_BLOG_RSS_TTL', 600),
        'item_limit'        => 30,
        'site_title'        => env('ACME_BLOG_SITE_TITLE', 'Blog'),
        'site_description'  => env('ACME_BLOG_SITE_DESC', ''),
    ],

    'comments' => [
        'enabled'         => true,
        'require_approval' => true,
        'allow_guest'     => true,
    ],

    'subscriptions' => [
        'enabled'             => true,
        'confirm_token_hours' => 48,
    ],

    'list_per_page' => 12,
];
