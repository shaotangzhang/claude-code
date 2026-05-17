<?php

declare(strict_types=1);

namespace Acme\Blog\Events;

final readonly class SubscriberConfirmed
{
    public function __construct(
        public string $subscriptionId,
        public string $email,
        public string $locale,
    ) {}
}
