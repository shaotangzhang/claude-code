<?php

declare(strict_types=1);

namespace Acme\Membership\Tests\Unit;

use Acme\Membership\Enums\BillingPeriod;
use Acme\Membership\Enums\SubscriptionStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function test_billing_period_advance(): void
    {
        $from = CarbonImmutable::create(2026, 6, 1);

        $this->assertTrue($from->equalTo(BillingPeriod::Once->advance($from)));
        $this->assertSame('2026-07-01', BillingPeriod::Monthly->advance($from)->format('Y-m-d'));
        $this->assertSame('2026-09-01', BillingPeriod::Quarterly->advance($from)->format('Y-m-d'));
        $this->assertSame('2027-06-01', BillingPeriod::Yearly->advance($from)->format('Y-m-d'));

        $this->assertFalse(BillingPeriod::Once->isRecurring());
        $this->assertTrue(BillingPeriod::Monthly->isRecurring());
    }

    public function test_status_grants_tier(): void
    {
        $this->assertTrue(SubscriptionStatus::Trialing->grantsTier());
        $this->assertTrue(SubscriptionStatus::Active->grantsTier());
        $this->assertTrue(SubscriptionStatus::PastDue->grantsTier());

        $this->assertFalse(SubscriptionStatus::Paused->grantsTier());
        $this->assertFalse(SubscriptionStatus::Canceled->grantsTier());
        $this->assertFalse(SubscriptionStatus::Expired->grantsTier());
    }
}
