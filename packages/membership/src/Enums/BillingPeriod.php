<?php

declare(strict_types=1);

namespace Acme\Membership\Enums;

use Carbon\CarbonInterface;

enum BillingPeriod: string
{
    case Once      = 'once';
    case Monthly   = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly    = 'yearly';

    public function isRecurring(): bool
    {
        return $this !== self::Once;
    }

    public function advance(CarbonInterface $from): CarbonInterface
    {
        return match ($this) {
            self::Once      => $from,
            self::Monthly   => $from->copy()->addMonth(),
            self::Quarterly => $from->copy()->addMonths(3),
            self::Yearly    => $from->copy()->addYear(),
        };
    }
}
