<?php

declare(strict_types=1);

namespace Acme\Commerce\Tests\Unit;

use Acme\Commerce\Models\StockLevel;
use PHPUnit\Framework\TestCase;

final class StockLevelTest extends TestCase
{
    public function test_available_clamps_at_zero(): void
    {
        $l = new StockLevel(['on_hand' => 3, 'reserved' => 5]);
        $this->assertSame(0, $l->available());
    }

    public function test_available_subtracts_reserved(): void
    {
        $l = new StockLevel(['on_hand' => 10, 'reserved' => 3]);
        $this->assertSame(7, $l->available());
    }
}
