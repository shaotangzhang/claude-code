<?php

declare(strict_types=1);

namespace Acme\Membership\Events;

final readonly class SubscriptionExpired
{
    public function __construct(
        public string $subscriptionId,
        public string $userId,
        public string $tierKey,
    ) {}
}
