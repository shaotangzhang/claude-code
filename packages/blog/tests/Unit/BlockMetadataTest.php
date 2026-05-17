<?php

declare(strict_types=1);

namespace Acme\Blog\Tests\Unit;

use Acme\Blog\Blocks\ArticleBlock;
use Acme\Blog\Blocks\ArticleListBlock;
use Acme\Blog\Blocks\LatestPostsBlock;
use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the three blog Block types satisfy the BlockType contract and
 * can be registered into a cms-core BlockRegistry. Rendering itself needs
 * a DB + view engine and lives in feature tests.
 */
final class BlockMetadataTest extends TestCase
{
    public function test_blocks_advertise_distinct_keys(): void
    {
        $this->assertSame('blog.article', ArticleBlock::key());
        $this->assertSame('blog.article-list', ArticleListBlock::key());
        $this->assertSame('blog.latest-posts', LatestPostsBlock::key());
    }

    public function test_register_into_cms_block_registry(): void
    {
        $reg = new InMemoryBlockRegistry();
        $reg->register(ArticleBlock::class);
        $reg->register(ArticleListBlock::class);
        $reg->register(LatestPostsBlock::class);

        $this->assertTrue($reg->has('blog.article'));
        $this->assertTrue($reg->has('blog.article-list'));
        $this->assertTrue($reg->has('blog.latest-posts'));
    }

    public function test_article_block_requires_id_or_slug(): void
    {
        $errors = (new ArticleBlock())->validate([]);
        $this->assertArrayHasKey('id', $errors);

        $this->assertSame([], (new ArticleBlock())->validate(['slug' => 'hello']));
        $this->assertSame([], (new ArticleBlock())->validate(['id'   => 'abc']));
    }
}
