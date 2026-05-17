<?php

declare(strict_types=1);

namespace Acme\Cart\Tests\Unit;

use Acme\Cart\Events\CartMerged;
use Acme\Cart\Events\CouponApplied;
use Acme\Cart\Events\CouponRemoved;
use Acme\Cart\Events\ItemAdded;
use Acme\Cart\Events\ItemRemoved;
use Acme\Cart\Events\ItemUpdated;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_events_carry_required_fields(): void
    {
        $a = new ItemAdded('c1', 'i1', 's1', 2);
        $this->assertSame(2, $a->quantity);

        $u = new ItemUpdated('c1', 'i1', 5);
        $this->assertSame(5, $u->quantity);

        $r = new ItemRemoved('c1', 'i1');
        $this->assertSame('i1', $r->itemId);

        $ca = new CouponApplied('c1', 'cp1', 'SAVE10');
        $this->assertSame('SAVE10', $ca->code);

        $cr = new CouponRemoved('c1', 'cp1', 'SAVE10');
        $this->assertSame('cp1', $cr->couponId);

        $m = new CartMerged('c-user', 'c-guest', 'u1');
        $this->assertSame('c-guest', $m->mergedFromGuestCartId);
    }
}
