<?php

declare(strict_types=1);

namespace Acme\MultiCurrencyPricing\Tests\Unit;

use Acme\MultiCurrencyPricing\Models\SkuPrice;
use PHPUnit\Framework\TestCase;

final class SkuPriceTest extends TestCase
{
    public function test_casts(): void
    {
        $p = new SkuPrice([
            'sku_id'           => 's1', 'currency' => 'USD',
            'price_cents'      => '1999',
            'list_price_cents' => '2499',
            'active'           => 1,
        ]);

        $this->assertSame(1999, $p->price_cents);
        $this->assertSame(2499, $p->list_price_cents);
        $this->assertTrue($p->active);
    }

    public function test_inactive(): void
    {
        $p = new SkuPrice(['sku_id' => 's1', 'currency' => 'USD', 'price_cents' => 0, 'active' => 0]);
        $this->assertFalse($p->active);
    }
}
