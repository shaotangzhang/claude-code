<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Tests\Unit;

use Acme\AbandonedCart\Events\CartAbandoned;
use Acme\AbandonedCart\Listeners\SendRecoveryReminder;
use Acme\Notifications\Dispatcher;
use PHPUnit\Framework\TestCase;

/**
 * Verify the listener composes a sensible notification payload —
 * the actual delivery layer is exercised in acme/notifications tests.
 */
final class SendRecoveryReminderTest extends TestCase
{
    public function test_payload_contains_url_and_total(): void
    {
        $captured = [];
        $dispatcher = new class($captured) extends Dispatcher {
            /** @var array<int,array{string,array<string,mixed>}> */
            public array $captured;
            public function __construct(array &$captured) { $this->captured = &$captured; }
            public function dispatch(string $eventType, array $payload): void
            {
                $this->captured[] = [$eventType, $payload];
            }
        };

        $event = new CartAbandoned(
            cartId: 'c1', userId: 'u1', email: 'a@b.test',
            recoveryToken: 'tok', recoveryUrl: 'https://shop/cart/recover/tok',
            itemCount: 2, totalCents: 1999, currency: 'USD',
        );

        (new SendRecoveryReminder($dispatcher))->handle($event);

        $this->assertCount(1, $dispatcher->captured);
        [$type, $payload] = $dispatcher->captured[0];
        $this->assertSame('cart.abandoned', $type);
        $this->assertSame('u1',       $payload['user_id']);
        $this->assertSame('a@b.test', $payload['recipient']);
        $this->assertStringContainsString('19.99',  $payload['body_text']);
        $this->assertStringContainsString('USD',    $payload['body_text']);
        $this->assertStringContainsString('https://shop/cart/recover/tok', $payload['body_text']);
        $this->assertStringContainsString('2 items', $payload['body_text']);
    }

    public function test_singular_grammar_for_one_item(): void
    {
        $captured = [];
        $dispatcher = new class($captured) extends Dispatcher {
            public array $captured;
            public function __construct(array &$captured) { $this->captured = &$captured; }
            public function dispatch(string $eventType, array $payload): void
            {
                $this->captured[] = [$eventType, $payload];
            }
        };

        (new SendRecoveryReminder($dispatcher))->handle(new CartAbandoned(
            cartId: 'c2', userId: null, email: null,
            recoveryToken: 't', recoveryUrl: '/r/t',
            itemCount: 1, totalCents: 500, currency: 'USD',
        ));

        $this->assertStringContainsString('1 item ',  $dispatcher->captured[0][1]['body_text']);
        $this->assertStringNotContainsString('1 items', $dispatcher->captured[0][1]['body_text']);
    }
}
