<?php

declare(strict_types=1);

namespace Acme\Checkout\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid           = 'paid';
    case Fulfilled      = 'fulfilled';
    case Canceled       = 'canceled';
    case Refunded       = 'refunded';
    case FailedPayment  = 'failed_payment';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Fulfilled, self::Canceled, self::Refunded], true);
    }

    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::Fulfilled], true);
    }
}
