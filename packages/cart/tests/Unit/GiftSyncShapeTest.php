<?php

declare(strict_types=1);

namespace Acme\Cart\Tests\Unit;

use Acme\Cart\Adjustments\GiftRegistry;
use Acme\Contracts\Commerce\CartGiftInsert;
use Acme\Contracts\Commerce\CartGiftProvider;
use PHPUnit\Framework\TestCase;

final class GiftSyncShapeTest extends TestCase
{
    public function test_registry_collects_providers(): void
    {
        $reg = new GiftRegistry();
        $this->assertSame([], $reg->all());

        $reg->register(new class implements CartGiftProvider {
            public function giftsFor(array $items, int $subtotalCents, string $currency, ?string $userId): array
            {
                return [new CartGiftInsert('test:gift', 'A gift', 'sku-1', 1)];
            }
        });

        $this->assertCount(1, $reg->all());

        $gifts = $reg->all()[0]->giftsFor([], 1000, 'USD', null);
        $this->assertCount(1, $gifts);
        $this->assertSame('test:gift', $gifts[0]->sourceKey);
        $this->assertSame('sku-1',     $gifts[0]->skuId);
        $this->assertSame(1,           $gifts[0]->quantity);
    }
}
