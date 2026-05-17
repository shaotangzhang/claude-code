<?php

declare(strict_types=1);

namespace Acme\Commerce\Events;

final readonly class PointsAwarded
{
    public function __construct(
        public string $userId,
        public int $points,
        public string $referenceType,
        public string $referenceId,
        public int $newBalance,
    ) {}
}
