<?php

declare(strict_types=1);

namespace Acme\Starter\Tests\Unit;

use Acme\Starter\Module\ComposerModuleRegistry;
use Acme\Starter\Tests\TestCase;

final class ModuleRegistryTest extends TestCase
{
    public function test_parses_acme_modules_from_installed_json(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'inst');
        file_put_contents($tmp, json_encode([
            'packages' => [
                [
                    'name'    => 'acme/cms-core',
                    'version' => '1.0.0',
                    'extra'   => ['acme' => ['module' => [
                        'key' => 'cms-core', 'title' => 'CMS Core', 'version' => '1.0.0',
                        'layer' => 2, 'depends' => ['auth', 'rbac'],
                    ]]],
                ],
                [
                    'name'    => 'vendor/unrelated',
                    'version' => '2.0.0',
                ],
            ],
        ]));

        $reg = new ComposerModuleRegistry($tmp);

        $this->assertTrue($reg->has('cms-core'));
        $this->assertFalse($reg->has('blog'));

        $m = $reg->get('cms-core');
        $this->assertNotNull($m);
        $this->assertSame('CMS Core', $m->title);
        $this->assertSame(['auth', 'rbac'], $m->depends);
        $this->assertSame(2, $m->layer);

        @unlink($tmp);
    }
}
