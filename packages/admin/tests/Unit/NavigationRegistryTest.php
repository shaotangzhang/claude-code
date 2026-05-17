<?php

declare(strict_types=1);

namespace Acme\Admin\Tests\Unit;

use Acme\Admin\Registry\InMemoryNavigationRegistry;
use Acme\Contracts\Auth\UserResolver;
use Acme\Contracts\Module\NavigationItem;
use PHPUnit\Framework\TestCase;

final class NavigationRegistryTest extends TestCase
{
    public function test_filters_by_area_and_sorts(): void
    {
        $reg = new InMemoryNavigationRegistry();
        $reg->registerMany([
            new NavigationItem('a', 'A', 'admin', order: 30, group: 'G1'),
            new NavigationItem('b', 'B', 'admin', order: 10, group: 'G1'),
            new NavigationItem('c', 'C', 'user-center', order: 10),
        ]);

        $admin = $reg->for('admin');
        $this->assertCount(2, $admin);
        $this->assertSame('b', $admin[0]->key);
        $this->assertSame('a', $admin[1]->key);

        $this->assertCount(1, $reg->for('user-center'));
    }

    public function test_hides_items_when_user_lacks_capability(): void
    {
        $resolver = new class implements UserResolver {
            public function currentUserId(): ?string { return 'u1'; }
            public function currentUserCan(string $capability): bool { return $capability === 'allowed'; }
        };

        $reg = new InMemoryNavigationRegistry($resolver);
        $reg->register(new NavigationItem('open',    'Open',    'admin'));
        $reg->register(new NavigationItem('gated',   'Gated',   'admin', capability: 'denied'));
        $reg->register(new NavigationItem('allowed', 'Allowed', 'admin', capability: 'allowed'));

        $items = $reg->for('admin');
        $keys  = array_map(fn ($i) => $i->key, $items);
        $this->assertContains('open', $keys);
        $this->assertContains('allowed', $keys);
        $this->assertNotContains('gated', $keys);
    }
}
