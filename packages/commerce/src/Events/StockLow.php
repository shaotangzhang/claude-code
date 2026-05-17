<?php

declare(strict_types=1);

namespace Acme\Commerce\Events;

final readonly class StockLow
{
    public function __construct(
        public string $skuId,
        public string $warehouseId,
        public int $available,
        public int $threshold,
    ) {}
}
