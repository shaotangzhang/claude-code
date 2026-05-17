<?php

declare(strict_types=1);

namespace Acme\Cart\Tests\Unit;

use Acme\Cart\Adjustments\AdjustmentRegistry;
use Acme\Contracts\Commerce\CartAdjustment;
use Acme\Contracts\Commerce\CartAdjustmentProvider;
use PHPUnit\Framework\TestCase;

final class AdjustmentRegistryTest extends TestCase
{
    public function test_registers_and_lists_providers(): void
    {
        $reg = new AdjustmentRegistry();
        $this->assertSame([], $reg->all());

        $provider = new class implements CartAdjustmentProvider {
            public function adjustmentsFor(array $items, int $subtotalCents, string $currency, ?string $userId): array
            {
                return [new CartAdjustment('test', 'Test', -100)];
            }
        };

        $reg->register($provider);
        $this->assertCount(1, $reg->all());

        $adj = $reg->all()[0]->adjustmentsFor([], 1000, 'USD', null);
        $this->assertCount(1, $adj);
        $this->assertSame(-100, $adj[0]->amountCents);
    }
}
