<?php

declare(strict_types=1);

namespace Acme\Membership\Events;

final readonly class SubscriptionStarted
{
    public function __construct(
        public string $subscriptionId,
        public string $userId,
        public string $planKey,
        public string $tierKey,
        public bool $isTrialing,
    ) {}
}
