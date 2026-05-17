<?php

declare(strict_types=1);

namespace Acme\Commerce\Listeners;

use Acme\Checkout\Events\OrderFulfilled;
use Acme\Commerce\Services\StockService;

/**
 * Convert reservations to actual outbound movements.
 */
final class HandleOrderFulfilled
{
    public function __construct(private readonly StockService $stock) {}

    public function handle(OrderFulfilled $event): void
    {
        $this->stock->shipForOrder($event->orderId);
    }
}
