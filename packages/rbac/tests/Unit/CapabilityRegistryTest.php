<?php

declare(strict_types=1);

namespace Acme\Rbac\Tests\Unit;

use Acme\Rbac\Registry\InMemoryCapabilityRegistry;
use PHPUnit\Framework\TestCase;

final class CapabilityRegistryTest extends TestCase
{
    public function test_registers_single_and_many(): void
    {
        $reg = new InMemoryCapabilityRegistry();
        $reg->register('blog.article.publish', 'Publish articles', 'Blog');

        $reg->registerMany([
            'blog.article.delete' => ['label' => 'Delete articles', 'group' => 'Blog'],
            'plain'               => 'A plain label',
        ]);

        $this->assertTrue($reg->has('blog.article.publish'));
        $this->assertTrue($reg->has('blog.article.delete'));
        $this->assertTrue($reg->has('plain'));
        $this->assertCount(3, $reg->all());

        $plain = collect($reg->all())->firstWhere('key', 'plain');
        $this->assertSame('A plain label', $plain['label']);
        $this->assertNull($plain['group']);
    }

    public function test_register_is_idempotent_by_key(): void
    {
        $reg = new InMemoryCapabilityRegistry();
        $reg->register('x', 'A');
        $reg->register('x', 'B', 'g');
        $this->assertCount(1, $reg->all());
        $this->assertSame('B', $reg->all()[0]['label']);
        $this->assertSame('g', $reg->all()[0]['group']);
    }
}
