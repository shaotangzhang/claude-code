<?php

declare(strict_types=1);

namespace Acme\Commerce\Events;

final readonly class ReturnRequested
{
    public function __construct(
        public string $returnId,
        public string $number,
        public string $orderId,
        public ?string $userId,
    ) {}
}
