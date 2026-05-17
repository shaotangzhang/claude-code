<?php

declare(strict_types=1);

return [
    'label'              => env('ACME_SHIPPING_FREE_LABEL', 'Free shipping'),
    // Cart subtotal at or above which the option appears (0 = always).
    'min_subtotal_cents' => (int) env('ACME_SHIPPING_FREE_MIN', 0),
    // Optional restriction by 2-letter country code list; empty = global.
    'countries'          => [],
];
