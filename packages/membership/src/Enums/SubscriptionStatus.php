<?php

declare(strict_types=1);

namespace Acme\Membership\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active   = 'active';
    case PastDue  = 'past_due';
    case Paused   = 'paused';
    case Canceled = 'canceled';
    case Expired  = 'expired';

    /** Statuses that grant the user the tier's perks. */
    public function grantsTier(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::PastDue => true,
            default => false,
        };
    }
}
