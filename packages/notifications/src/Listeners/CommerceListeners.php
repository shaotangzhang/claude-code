<?php

declare(strict_types=1);

namespace Acme\Notifications\Listeners;

use Acme\Commerce\Events\ReturnRequested;
use Acme\Commerce\Events\StockLow;
use Acme\Notifications\Dispatcher;

final class CommerceListeners
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function onReturnRequested(ReturnRequested $e): void
    {
        $this->dispatcher->dispatch('return.requested', [
            'user_id'   => $e->userId,
            'subject'   => "Return {$e->number} requested",
            'body_text' => "Your return request {$e->number} has been received.",
        ]);
    }

    public function onStockLow(StockLow $e): void
    {
        $this->dispatcher->dispatch('stock.low', [
            'subject'   => "SKU {$e->skuId} low stock",
            'body_text' => "Warehouse {$e->warehouseId} for SKU {$e->skuId} is at {$e->available} units (threshold {$e->threshold}).",
        ]);
    }
}
