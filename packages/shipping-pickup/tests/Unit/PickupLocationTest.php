<?php

declare(strict_types=1);

namespace Acme\ShippingPickup\Tests\Unit;

use Acme\ShippingPickup\Models\PickupLocation;
use PHPUnit\Framework\TestCase;

final class PickupLocationTest extends TestCase
{
    public function test_casts(): void
    {
        $loc = new PickupLocation([
            'active'         => 1,
            'ready_days_min' => '2',
            'ready_days_max' => '5',
        ]);
        $this->assertTrue($loc->active);
        $this->assertSame(2, $loc->ready_days_min);
        $this->assertSame(5, $loc->ready_days_max);
    }
}
