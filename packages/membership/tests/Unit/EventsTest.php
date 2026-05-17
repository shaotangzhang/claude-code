<?php

declare(strict_types=1);

namespace Acme\Membership\Tests\Unit;

use Acme\Membership\Events\PaymentDue;
use Acme\Membership\Events\PaymentReceived;
use Acme\Membership\Events\SubscriptionCanceled;
use Acme\Membership\Events\SubscriptionExpired;
use Acme\Membership\Events\SubscriptionPaused;
use Acme\Membership\Events\SubscriptionRenewed;
use Acme\Membership\Events\SubscriptionResumed;
use Acme\Membership\Events\SubscriptionStarted;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_lifecycle_events_carry_required_fields(): void
    {
        $started = new SubscriptionStarted('s1', 'u1', 'gold-monthly', 'gold', true);
        $this->assertTrue($started->isTrialing);

        $renewed = new SubscriptionRenewed('s1', 'u1', '2027-01-01T00:00:00+00:00');
        $this->assertStringContainsString('2027', $renewed->newPeriodEndIso);

        $paused = new SubscriptionPaused('s1', 'u1', null);
        $this->assertNull($paused->untilIso);

        $resumed = new SubscriptionResumed('s1', 'u1');
        $this->assertSame('u1', $resumed->userId);

        $canceled = new SubscriptionCanceled('s1', 'u1', false);
        $this->assertFalse($canceled->immediate);

        $expired = new SubscriptionExpired('s1', 'u1', 'gold');
        $this->assertSame('gold', $expired->tierKey);
    }

    public function test_payment_events(): void
    {
        $due = new PaymentDue('s1', 'u1', 'gold-monthly', 9900, 'CNY', '2026-07-01T00:00:00+00:00', true);
        $this->assertSame(9900, $due->amountCents);
        $this->assertTrue($due->isInitial);

        $rcv = new PaymentReceived('s1', 9900, 'CNY', 'pi_abc123', '2026-07-01T00:01:00+00:00');
        $this->assertSame('pi_abc123', $rcv->referenceId);
    }
}
