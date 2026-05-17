<?php

declare(strict_types=1);

namespace Acme\Membership\Tests\Unit;

use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use Acme\Membership\Blocks\PlanGridBlock;
use PHPUnit\Framework\TestCase;

final class BlockTest extends TestCase
{
    public function test_plan_grid_block_registers(): void
    {
        $reg = new InMemoryBlockRegistry();
        $reg->register(PlanGridBlock::class);

        $this->assertTrue($reg->has('membership.plans'));
        $this->assertSame('Membership · Plans', PlanGridBlock::label());
    }
}
