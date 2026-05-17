<?php

declare(strict_types=1);

namespace Acme\Membership\Events;

final readonly class SubscriptionResumed
{
    public function __construct(
        public string $subscriptionId,
        public string $userId,
    ) {}
}
