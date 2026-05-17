<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Tests\Unit;

use Acme\InventoryFefo\Models\Batch;
use PHPUnit\Framework\TestCase;

final class BatchTest extends TestCase
{
    public function test_available_subtracts_reserved(): void
    {
        $b = new Batch(['on_hand' => 100, 'reserved' => 30]);
        $this->assertSame(70, $b->available());
    }

    public function test_available_clamps_at_zero_if_oversold(): void
    {
        $b = new Batch(['on_hand' => 5, 'reserved' => 20]);
        $this->assertSame(0, $b->available());
    }
}
