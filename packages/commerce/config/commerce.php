<?php

declare(strict_types=1);

return [
    'inventory' => [
        // When true, listener reserves stock on OrderPaid and decrements
        // on OrderFulfilled. When false, commerce only audits movements
        // and the host project drives them explicitly.
        'auto_reserve_on_paid' => true,
        // Threshold (units) below which StockLow event fires; null disables.
        'low_threshold' => 5,
    ],

    'loyalty' => [
        'enabled' => true,
        // Points awarded per minor unit (cent) of order total.
        'points_per_cent' => env('ACME_LOYALTY_RATE', 0.01),
        // Cents per point when redeeming.
        'redeem_cents_per_point' => 1,
    ],

    'returns' => [
        // Days after order paid_at that customer can still open an RMA.
        'window_days' => 30,
    ],
];
