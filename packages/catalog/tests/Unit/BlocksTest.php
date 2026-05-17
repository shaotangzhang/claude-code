<?php

declare(strict_types=1);

namespace Acme\Catalog\Tests\Unit;

use Acme\Catalog\Blocks\CategoryFilterBlock;
use Acme\Catalog\Blocks\FeaturedProductsBlock;
use Acme\Catalog\Blocks\ProductBlock;
use Acme\Catalog\Blocks\ProductGridBlock;
use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use PHPUnit\Framework\TestCase;

final class BlocksTest extends TestCase
{
    public function test_block_keys_are_distinct(): void
    {
        $keys = [
            ProductBlock::key(),
            ProductGridBlock::key(),
            CategoryFilterBlock::key(),
            FeaturedProductsBlock::key(),
        ];
        $this->assertSame($keys, array_unique($keys));
    }

    public function test_blocks_register_into_cms_registry(): void
    {
        $reg = new InMemoryBlockRegistry();
        foreach ([
            ProductBlock::class, ProductGridBlock::class,
            CategoryFilterBlock::class, FeaturedProductsBlock::class,
        ] as $cls) {
            $reg->register($cls);
        }

        $this->assertTrue($reg->has('catalog.product'));
        $this->assertTrue($reg->has('catalog.product-grid'));
        $this->assertTrue($reg->has('catalog.category-filter'));
        $this->assertTrue($reg->has('catalog.featured'));
        $this->assertCount(4, $reg->all());
    }

    public function test_product_block_requires_id_or_slug(): void
    {
        $errors = (new ProductBlock())->validate([]);
        $this->assertArrayHasKey('id', $errors);
        $this->assertSame([], (new ProductBlock())->validate(['slug' => 'x']));
    }
}
