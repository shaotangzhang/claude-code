<?php

declare(strict_types=1);

namespace Acme\Commerce\Events;

final readonly class StockReserved
{
    /** @param array<string,int> $skuQuantities sku_id => qty */
    public function __construct(
        public string $orderId,
        public array $skuQuantities,
    ) {}
}
