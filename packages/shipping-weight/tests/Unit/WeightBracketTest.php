<?php

declare(strict_types=1);

namespace Acme\ShippingWeight\Tests\Unit;

use Acme\ShippingWeight\Models\WeightBracket;
use PHPUnit\Framework\TestCase;

final class WeightBracketTest extends TestCase
{
    public function test_open_ended_upper_bound(): void
    {
        $b = new WeightBracket(['min_g' => 1_000, 'max_g' => null]);
        $this->assertFalse($b->matches(999));
        $this->assertTrue($b->matches(1_000));
        $this->assertTrue($b->matches(10_000_000));
    }

    public function test_bounded_bracket(): void
    {
        $b = new WeightBracket(['min_g' => 0, 'max_g' => 500]);
        $this->assertTrue($b->matches(0));
        $this->assertTrue($b->matches(500));
        $this->assertFalse($b->matches(501));
    }
}
