<?php

declare(strict_types=1);

namespace Acme\Cart\Tests\Unit;

use Acme\Cart\Shipping\FlatRateShipping;
use Orchestra\Testbench\TestCase;

final class FlatRateShippingTest extends TestCase
{
    public function test_returns_one_option_at_configured_rate(): void
    {
        config()->set('acme.cart.shipping.flat_rate_cents', 599);
        config()->set('acme.cart.shipping.free_above_cents', 0);

        $options = (new FlatRateShipping())->options(['__subtotal_cents' => 1_000], 'USD', null);

        $this->assertCount(1, $options);
        $this->assertSame(599, $options[0]->costCents);
        $this->assertSame('standard', $options[0]->key);
    }

    public function test_free_shipping_above_threshold(): void
    {
        config()->set('acme.cart.shipping.flat_rate_cents', 599);
        config()->set('acme.cart.shipping.free_above_cents', 5_000);

        $options = (new FlatRateShipping())->options(['__subtotal_cents' => 5_000], 'USD', null);

        $this->assertSame(0, $options[0]->costCents);
        $this->assertStringContainsString('Free', $options[0]->label);
    }
}
