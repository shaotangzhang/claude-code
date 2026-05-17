<?php

declare(strict_types=1);

namespace Acme\ShippingZones\Tests\Unit;

use Acme\ShippingZones\Models\ZoneRate;
use PHPUnit\Framework\TestCase;

final class ZoneRateTest extends TestCase
{
    public function test_applies_to_when_no_bounds(): void
    {
        $r = new ZoneRate(['cost_cents' => 599, 'currency' => 'USD']);
        $this->assertTrue($r->appliesTo(0));
        $this->assertTrue($r->appliesTo(999_999_999));
    }

    public function test_below_min_subtotal_rejects(): void
    {
        $r = new ZoneRate(['min_subtotal_cents' => 10_000]);
        $this->assertFalse($r->appliesTo(5_000));
        $this->assertTrue($r->appliesTo(10_000));
        $this->assertTrue($r->appliesTo(50_000));
    }

    public function test_above_max_subtotal_rejects(): void
    {
        $r = new ZoneRate(['max_subtotal_cents' => 10_000]);
        $this->assertTrue($r->appliesTo(5_000));
        $this->assertTrue($r->appliesTo(10_000));
        $this->assertFalse($r->appliesTo(10_001));
    }

    public function test_both_bounds_form_a_window(): void
    {
        $r = new ZoneRate(['min_subtotal_cents' => 1_000, 'max_subtotal_cents' => 5_000]);
        $this->assertFalse($r->appliesTo(999));
        $this->assertTrue($r->appliesTo(1_000));
        $this->assertTrue($r->appliesTo(5_000));
        $this->assertFalse($r->appliesTo(5_001));
    }
}
