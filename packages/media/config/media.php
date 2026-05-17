<?php

declare(strict_types=1);

return [
    'disk'        => env('ACME_MEDIA_DISK', 'public'),
    'max_size_mb' => (int) env('ACME_MEDIA_MAX_MB', 25),
    'image' => [
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif', 'image/svg+xml'],
    ],
];
