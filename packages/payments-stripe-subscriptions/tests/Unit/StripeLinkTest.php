<?php

declare(strict_types=1);

namespace Acme\PaymentsStripeSubscriptions\Tests\Unit;

use Acme\PaymentsStripeSubscriptions\Models\StripeLink;
use Orchestra\Testbench\TestCase;

final class StripeLinkTest extends TestCase
{
    public function test_casts_current_period_end_to_datetime(): void
    {
        $l = new StripeLink([
            'subscription_id'        => 's1',
            'stripe_customer_id'     => 'cus_x',
            'stripe_subscription_id' => 'sub_x',
            'status'                 => 'active',
            'current_period_end'     => '2027-12-31 23:59:59',
        ]);

        $this->assertSame('s1', $l->subscription_id);
        $this->assertSame('active', $l->status);
        $this->assertInstanceOf(\Carbon\Carbon::class, $l->current_period_end);
    }
}
