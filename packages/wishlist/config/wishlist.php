<?php

declare(strict_types=1);

return [
    'route_prefix' => env('ACME_WISHLIST_PREFIX', 'wishlist'),

    // Max items in one wishlist; null = unlimited.
    'max_items_per_list' => 500,

    // Allow named multi-list ("Birthday", "Wedding") in addition to the
    // default list. When false, only one list per user.
    'allow_multi_list' => true,
];
