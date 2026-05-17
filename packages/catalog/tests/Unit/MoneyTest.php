<?php

declare(strict_types=1);

namespace Acme\Catalog\Tests\Unit;

use Acme\Catalog\Support\Money;
use Orchestra\Testbench\TestCase;

final class MoneyTest extends TestCase
{
    public function test_default_currency_formats_without_suffix(): void
    {
        config()->set('acme.catalog.currency.default', 'USD');
        config()->set('acme.catalog.currency.symbol', '$');
        config()->set('acme.catalog.currency.minor_unit', 2);
        config()->set('acme.catalog.currency.symbol_position', 'left');

        $this->assertSame('$12.34', Money::format(1234, 'USD'));
    }

    public function test_non_default_currency_carries_code_suffix(): void
    {
        config()->set('acme.catalog.currency.default', 'USD');
        config()->set('acme.catalog.currency.symbol', '$');
        config()->set('acme.catalog.currency.minor_unit', 2);

        $this->assertSame('$12.34 EUR', Money::format(1234, 'EUR'));
    }

    public function test_symbol_position_right(): void
    {
        config()->set('acme.catalog.currency.default', 'CNY');
        config()->set('acme.catalog.currency.symbol', '¥');
        config()->set('acme.catalog.currency.minor_unit', 2);
        config()->set('acme.catalog.currency.symbol_position', 'right');

        $this->assertSame('99.00 ¥', Money::format(9900, 'CNY'));
    }
}
