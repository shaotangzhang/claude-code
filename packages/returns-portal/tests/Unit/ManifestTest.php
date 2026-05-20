<?php

declare(strict_types=1);

namespace Acme\ReturnsPortal\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verify the navigation contribution + SP wiring are well-formed.
 * Deeper integration tests need a real DB + auth context; live in
 * commerce/checkout's tests.
 */
final class ManifestTest extends TestCase
{
    public function test_navigation_targets_existing_route_name(): void
    {
        $nav = require __DIR__ . '/../../src/navigation.php';
        $this->assertNotEmpty($nav);
        $this->assertSame('acme.returns-portal.index', $nav[0]->route);
        $this->assertSame('user-center', $nav[0]->area);
    }
}
