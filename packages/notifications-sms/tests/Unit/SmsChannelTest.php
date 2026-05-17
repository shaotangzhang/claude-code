<?php

declare(strict_types=1);

namespace Acme\NotificationsSms\Tests\Unit;

use Acme\NotificationsSms\SmsChannel;
use Acme\NotificationsSms\SmsClient;
use Orchestra\Testbench\TestCase;
use Psr\Log\NullLogger;

final class SmsChannelTest extends TestCase
{
    public function test_key(): void
    {
        $ch = new SmsChannel($this->fakeClient(), new NullLogger());
        $this->assertSame('sms', $ch->key());
    }

    public function test_unconfigured_logs_when_soft_mode(): void
    {
        config()->set('acme.notifications-sms.from', '');
        config()->set('acme.notifications-sms.log_when_unconfigured', true);

        // Should not throw.
        (new SmsChannel($this->fakeClient(), new NullLogger()))
            ->send(['recipient' => '+15551234', 'subject' => 'hi']);
        $this->assertTrue(true);
    }

    public function test_unconfigured_throws_when_strict(): void
    {
        config()->set('acme.notifications-sms.from', '');
        config()->set('acme.notifications-sms.sid', '');
        config()->set('acme.notifications-sms.log_when_unconfigured', false);

        $this->expectException(\RuntimeException::class);
        (new SmsChannel($this->fakeClient(), new NullLogger()))
            ->send(['recipient' => '+15551234', 'subject' => 'hi']);
    }

    public function test_skips_when_recipient_empty(): void
    {
        config()->set('acme.notifications-sms.from', '+15550000');
        config()->set('acme.notifications-sms.sid', 'AC');
        config()->set('acme.notifications-sms.token', 'tok');

        // No throw, no call: just returns.
        $client = new class extends SmsClient {
            public bool $called = false;
            public function __construct() {}
            public function send(string $from, string $to, string $body): array { $this->called = true; return []; }
        };

        (new SmsChannel($client, new NullLogger()))->send(['recipient' => '', 'subject' => 'hi']);
        $this->assertFalse($client->called);
    }

    private function fakeClient(): SmsClient
    {
        return new class extends SmsClient {
            public function __construct() {}
            public function send(string $from, string $to, string $body): array { return ['sid' => 'SM1']; }
        };
    }
}
