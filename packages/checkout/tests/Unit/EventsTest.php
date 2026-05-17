<?php

declare(strict_types=1);

namespace Acme\Checkout\Tests\Unit;

use Acme\Checkout\Events\OrderCanceled;
use Acme\Checkout\Events\OrderFulfilled;
use Acme\Checkout\Events\OrderPaid;
use Acme\Checkout\Events\OrderPlaced;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_lifecycle_events(): void
    {
        $placed = new OrderPlaced('o1', 'N1', 'u1', 12345, 'USD');
        $this->assertSame(12345, $placed->totalCents);

        $paid = new OrderPaid('o1', 'N1', 'u1', 'tx1', '2027-01-01T00:00:00+00:00');
        $this->assertSame('tx1', $paid->transactionId);

        $canceled = new OrderCanceled('o1', 'N1', 'u1', 'user-requested');
        $this->assertSame('user-requested', $canceled->reason);

        $fulfilled = new OrderFulfilled('o1', 'N1', 'u1', '2027-01-02T00:00:00+00:00');
        $this->assertSame('o1', $fulfilled->orderId);
    }
}
