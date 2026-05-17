<?php

declare(strict_types=1);

namespace Acme\Payments\Tests\Unit;

use Acme\Payments\Events\PaymentFailed;
use Acme\Payments\Events\PaymentRefunded;
use Acme\Payments\Events\PaymentSucceeded;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_events_carry_required_fields(): void
    {
        $s = new PaymentSucceeded('tx1', 'manual', 'manual_tx1', 'order', 'o1', 1000, 'USD', '2027-01-01T00:00:00+00:00');
        $this->assertSame('order', $s->relatedType);
        $this->assertSame(1000, $s->amountCents);

        $f = new PaymentFailed('tx1', 'manual', 'order', 'o1', 'card declined');
        $this->assertSame('card declined', $f->reason);

        $r = new PaymentRefunded('tx1', 'manual', 'order', 'o1', 500, 'USD');
        $this->assertSame(500, $r->amountCents);
    }
}
