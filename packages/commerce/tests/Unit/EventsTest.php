<?php

declare(strict_types=1);

namespace Acme\Commerce\Tests\Unit;

use Acme\Commerce\Events\PointsAwarded;
use Acme\Commerce\Events\ReturnRequested;
use Acme\Commerce\Events\ReviewSubmitted;
use Acme\Commerce\Events\StockLow;
use Acme\Commerce\Events\StockReserved;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_events_carry_fields(): void
    {
        $low = new StockLow('sku1', 'wh1', 2, 5);
        $this->assertSame(5, $low->threshold);

        $res = new StockReserved('o1', ['sku1' => 3, 'sku2' => 1]);
        $this->assertSame(3, $res->skuQuantities['sku1']);

        $pts = new PointsAwarded('u1', 50, 'order', 'o1', 250);
        $this->assertSame(250, $pts->newBalance);

        $rma = new ReturnRequested('r1', 'RMA-XYZ', 'o1', 'u1');
        $this->assertSame('RMA-XYZ', $rma->number);

        $rev = new ReviewSubmitted('rv1', 'p1', 'u1', 4, 'pending');
        $this->assertSame(4, $rev->rating);
    }
}
