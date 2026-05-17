<?php

declare(strict_types=1);

namespace Acme\ShippingFree\Tests\Unit;

use Acme\Contracts\Commerce\Address;
use Acme\ShippingFree\FreeShippingMethod;
use Orchestra\Testbench\TestCase;

final class FreeShippingMethodTest extends TestCase
{
    public function test_always_offered_at_zero_threshold(): void
    {
        config()->set('acme.shipping-free.min_subtotal_cents', 0);
        $opts = (new FreeShippingMethod())->rate([], 'USD', null, 100);
        $this->assertCount(1, $opts);
        $this->assertSame(0, $opts[0]->costCents);
        $this->assertSame('free', $opts[0]->key);
    }

    public function test_hidden_below_threshold(): void
    {
        config()->set('acme.shipping-free.min_subtotal_cents', 10_000);
        $this->assertSame([], (new FreeShippingMethod())->rate([], 'USD', null, 9_999));
    }

    public function test_country_allowlist_filters(): void
    {
        config()->set('acme.shipping-free.min_subtotal_cents', 0);
        config()->set('acme.shipping-free.countries', ['US']);

        $this->assertCount(1, (new FreeShippingMethod())->rate([], 'USD', new Address('US'), 100));
        $this->assertSame([], (new FreeShippingMethod())->rate([], 'USD', new Address('CA'), 100));
    }
}
