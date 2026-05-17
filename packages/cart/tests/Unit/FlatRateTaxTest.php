<?php

declare(strict_types=1);

namespace Acme\Cart\Tests\Unit;

use Acme\Cart\Tax\FlatRateTax;
use Orchestra\Testbench\TestCase;

final class FlatRateTaxTest extends TestCase
{
    public function test_zero_when_rate_is_zero(): void
    {
        config()->set('acme.cart.tax.flat_rate_bps', 0);

        $this->assertSame(0, (new FlatRateTax())->calculate(10_000, 'USD', null));
    }

    public function test_basis_points_math(): void
    {
        config()->set('acme.cart.tax.flat_rate_bps', 2_000); // 20%

        $this->assertSame(2_000, (new FlatRateTax())->calculate(10_000, 'USD', null));
        $this->assertSame(2_499, (new FlatRateTax())->calculate(12_497, 'USD', null));
    }

    public function test_zero_when_subtotal_is_zero(): void
    {
        config()->set('acme.cart.tax.flat_rate_bps', 1_000);

        $this->assertSame(0, (new FlatRateTax())->calculate(0, 'USD', null));
    }
}
