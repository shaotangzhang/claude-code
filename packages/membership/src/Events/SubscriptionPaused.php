<?php

declare(strict_types=1);

namespace Acme\Membership\Events;

final readonly class SubscriptionPaused
{
    public function __construct(
        public string $subscriptionId,
        public string $userId,
        public ?string $untilIso,
    ) {}
}
