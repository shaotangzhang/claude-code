<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The MakeThemeCommand is hard to exercise without a full Laravel container;
 * this test asserts the stub set is internally consistent — the 4 stubs the
 * command expects all exist and contain the expected placeholders.
 */
final class MakeThemeCommandTest extends TestCase
{
    public function test_required_stubs_exist_and_contain_placeholders(): void
    {
        $root = realpath(__DIR__ . '/../../stubs/theme');
        $this->assertNotFalse($root, 'stubs/theme directory should exist');

        $expected = [
            'composer.json',
            'theme.json',
            'ServiceProvider.php.stub',
            'views/layouts/default.blade.php.stub',
        ];

        foreach ($expected as $rel) {
            $path = "{$root}/{$rel}";
            $this->assertFileExists($path, "Missing stub: {$rel}");
            $body = (string) file_get_contents($path);
            $this->assertNotEmpty($body, "Empty stub: {$rel}");
        }

        // composer.json stub must reference the studly + key placeholders
        $composer = (string) file_get_contents("{$root}/composer.json");
        $this->assertStringContainsString('{{ key }}', $composer);
        $this->assertStringContainsString('{{ studly }}', $composer);
    }
}
