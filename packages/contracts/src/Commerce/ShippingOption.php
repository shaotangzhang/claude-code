<?php

declare(strict_types=1);

namespace Acme\Contracts\Commerce;

final readonly class ShippingOption
{
    public function __construct(
        public string $key,
        public string $label,
        public int $costCents,
        public string $currency,
        public ?int $estimatedDaysMin = null,
        public ?int $estimatedDaysMax = null,
    ) {}
}
