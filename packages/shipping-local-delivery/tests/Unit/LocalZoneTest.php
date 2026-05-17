<?php

declare(strict_types=1);

namespace Acme\ShippingLocalDelivery\Tests\Unit;

use Acme\ShippingLocalDelivery\Models\LocalRate;
use Acme\ShippingLocalDelivery\Models\LocalZone;
use PHPUnit\Framework\TestCase;

final class LocalZoneTest extends TestCase
{
    public function test_postal_prefix_matching(): void
    {
        $zone = new LocalZone(['postal_prefixes_json' => ['100', '101', 'SW1']]);

        $this->assertTrue($zone->matchesPostal('100001'));
        $this->assertTrue($zone->matchesPostal(' 1010 23 '));
        $this->assertTrue($zone->matchesPostal('sw1a 1aa'));   // case-insensitive + space-tolerant
        $this->assertFalse($zone->matchesPostal('102000'));
        $this->assertFalse($zone->matchesPostal(null));
        $this->assertFalse($zone->matchesPostal(''));
    }

    public function test_rate_subtotal_floor(): void
    {
        $r = new LocalRate(['min_subtotal_cents' => 5_000]);
        $this->assertFalse($r->appliesTo(4_999));
        $this->assertTrue($r->appliesTo(5_000));

        $unbounded = new LocalRate([]);
        $this->assertTrue($unbounded->appliesTo(0));
    }
}
