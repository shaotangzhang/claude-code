<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

interface ShippingCalculator
{
    /**
     * @param  list<array{sku_id:string,quantity:int,weight_g?:int}>  $items
     * @return list<ShippingOption>
     */
    public function options(array $items, string $currency, ?Address $destination): array;
}
