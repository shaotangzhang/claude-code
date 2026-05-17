<?php

declare(strict_types=1);

namespace Acme\Notifications\Tests\Unit;

use Acme\Notifications\ChannelRegistry;
use Acme\Notifications\Channels\Channel;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ChannelRegistryTest extends TestCase
{
    public function test_register_and_resolve(): void
    {
        $reg = new ChannelRegistry();
        $captured = [];
        $reg->register($this->fakeChannel('mail', $captured));
        $reg->register($this->fakeChannel('log',  $captured));

        $this->assertTrue($reg->has('mail'));
        $this->assertTrue($reg->has('log'));
        $this->assertFalse($reg->has('sms'));

        $reg->get('mail')->send(['subject' => 'hi']);
        $this->assertSame([['mail', 'hi']], $captured);
    }

    public function test_unknown_channel_throws(): void
    {
        $this->expectException(RuntimeException::class);
        (new ChannelRegistry())->get('webhook');
    }

    private function fakeChannel(string $key, array &$captured): Channel
    {
        return new class($key, $captured) implements Channel {
            public function __construct(private readonly string $k, private array &$captured) {}
            public function key(): string { return $this->k; }
            public function send(array $payload): void { $this->captured[] = [$this->k, $payload['subject'] ?? '']; }
        };
    }
}
