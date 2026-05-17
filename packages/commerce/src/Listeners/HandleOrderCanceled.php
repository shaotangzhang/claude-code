<?php

declare(strict_types=1);

namespace Acme\Commerce\Listeners;

use Acme\Checkout\Events\OrderCanceled;
use Acme\Commerce\Services\StockService;

/** Release any reservations held against a canceled order. */
final class HandleOrderCanceled
{
    public function __construct(private readonly StockService $stock) {}

    public function handle(OrderCanceled $event): void
    {
        $this->stock->releaseForOrder($event->orderId);
    }
}
