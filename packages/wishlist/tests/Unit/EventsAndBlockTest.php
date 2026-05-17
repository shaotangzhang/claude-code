<?php

declare(strict_types=1);

namespace Acme\Wishlist\Tests\Unit;

use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use Acme\Wishlist\Blocks\WishlistSummaryBlock;
use Acme\Wishlist\Events\WishlistItemAdded;
use Acme\Wishlist\Events\WishlistItemMovedToCart;
use PHPUnit\Framework\TestCase;

final class EventsAndBlockTest extends TestCase
{
    public function test_events_carry_fields(): void
    {
        $a = new WishlistItemAdded('list-1', 'item-1', 'user-1', 'sku-1');
        $this->assertSame('sku-1', $a->skuId);

        $m = new WishlistItemMovedToCart('user-1', 'sku-1', 2, 'cart-1');
        $this->assertSame(2, $m->quantity);
        $this->assertSame('cart-1', $m->cartId);
    }

    public function test_block_registers(): void
    {
        $reg = new InMemoryBlockRegistry();
        $reg->register(WishlistSummaryBlock::class);
        $this->assertTrue($reg->has('wishlist.summary'));
        $this->assertSame('Wishlist · Mini summary', WishlistSummaryBlock::label());
    }
}
