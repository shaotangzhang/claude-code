<?php

declare(strict_types=1);

namespace Acme\CmsCore\Tests\Unit;

use Acme\CmsCore\Registry\InMemoryLayoutRegistry;
use Acme\CmsCore\Registry\InMemoryThemeRegistry;
use Acme\Contracts\Cms\LayoutDefinition;
use Acme\Contracts\Cms\SlotDefinition;
use Acme\Contracts\Cms\ThemeManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RegistriesTest extends TestCase
{
    public function test_layout_registry(): void
    {
        $reg = new InMemoryLayoutRegistry();
        $reg->register(new LayoutDefinition('a', 'A', 'view.a', [new SlotDefinition('main', 'Main')]));

        $this->assertTrue($reg->has('a'));
        $this->assertSame('view.a', $reg->resolve('a')->template);
    }

    public function test_theme_registry_activate(): void
    {
        $reg = new InMemoryThemeRegistry();
        $reg->register(new ThemeManifest('t1', 'T1', '1.0', '/v', '/a'));
        $reg->register(new ThemeManifest('t2', 'T2', '1.0', '/v', '/a'));

        $this->assertNull($reg->active());
        $reg->activate('t2');
        $this->assertSame('t2', $reg->active()->key);
    }

    public function test_theme_registry_rejects_unknown(): void
    {
        $this->expectException(RuntimeException::class);
        (new InMemoryThemeRegistry())->activate('missing');
    }
}
