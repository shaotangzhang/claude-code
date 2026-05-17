<?php

declare(strict_types=1);

namespace Acme\SkuBundles\Tests\Unit;

use Acme\SkuBundles\Events\BundleAddedToCart;
use Acme\SkuBundles\Models\Bundle;
use PHPUnit\Framework\TestCase;

final class BundleTest extends TestCase
{
    public function test_bundle_casts(): void
    {
        $b = new Bundle(['price_cents' => '4999', 'active' => '1']);
        $this->assertSame(4999, $b->price_cents);
        $this->assertTrue($b->active);
    }

    public function test_event_fields(): void
    {
        $e = new BundleAddedToCart('cart-1', 'summer-pack', 'bundle:summer-pack:abc', 3);
        $this->assertSame('summer-pack', $e->bundleKey);
        $this->assertSame(3,             $e->childLineCount);
    }
}
