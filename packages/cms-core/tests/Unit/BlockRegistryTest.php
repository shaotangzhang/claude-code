<?php

declare(strict_types=1);

namespace Acme\CmsCore\Tests\Unit;

use Acme\CmsCore\Blocks\HtmlBlock;
use Acme\CmsCore\Blocks\TextBlock;
use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use Acme\CmsCore\Rendering\RenderContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BlockRegistryTest extends TestCase
{
    public function test_registers_and_resolves_block_types(): void
    {
        $reg = new InMemoryBlockRegistry();
        $reg->register(TextBlock::class);
        $reg->register(HtmlBlock::class);

        $this->assertTrue($reg->has('cms.text'));
        $this->assertTrue($reg->has('cms.html'));
        $this->assertInstanceOf(TextBlock::class, $reg->resolve('cms.text'));
    }

    public function test_rejects_non_block_classes(): void
    {
        $this->expectException(RuntimeException::class);
        (new InMemoryBlockRegistry())->register(\stdClass::class);
    }

    public function test_text_block_escapes_and_paragraphs(): void
    {
        $block = new TextBlock();
        $html  = $block->render(['body' => "Hello <b>world</b>\n\nNext paragraph"], new RenderContext('en'));

        $this->assertStringContainsString('Hello &lt;b&gt;world&lt;/b&gt;', $html);
        $this->assertStringContainsString('<p>Next paragraph</p>', $html);
    }

    public function test_html_block_validates_required_field(): void
    {
        $errors = (new HtmlBlock())->validate([]);
        $this->assertArrayHasKey('html', $errors);
    }
}
