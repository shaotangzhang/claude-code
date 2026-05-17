<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Tests\Unit;

use Acme\AbandonedCart\Events\CartAbandoned;
use Acme\AbandonedCart\Events\CartRecovered;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_cart_abandoned_carries_fields(): void
    {
        $e = new CartAbandoned(
            cartId: 'c1', userId: 'u1', email: 'a@b.test',
            recoveryToken: 'tok', recoveryUrl: 'https://shop/cart/recover/tok',
            itemCount: 3, totalCents: 1999, currency: 'USD',
        );
        $this->assertSame('c1', $e->cartId);
        $this->assertSame(3,    $e->itemCount);
        $this->assertSame('tok', $e->recoveryToken);
    }

    public function test_cart_recovered_carries_fields(): void
    {
        $e = new CartRecovered('c1', null);
        $this->assertSame('c1', $e->cartId);
        $this->assertNull($e->userId);
    }
}
