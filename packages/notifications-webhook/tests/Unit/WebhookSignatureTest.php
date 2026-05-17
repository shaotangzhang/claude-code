<?php

declare(strict_types=1);

namespace Acme\NotificationsWebhook\Tests\Unit;

use Acme\NotificationsWebhook\WebhookSignature;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureTest extends TestCase
{
    public function test_sign_format(): void
    {
        $h = WebhookSignature::sign('{"a":1}', 'whsec_test', 1_700_000_000);
        $this->assertSame(1, preg_match('/^t=1700000000,v1=[a-f0-9]{64}$/', $h));
    }

    public function test_sign_is_deterministic(): void
    {
        $a = WebhookSignature::sign('payload', 'sec', 100);
        $b = WebhookSignature::sign('payload', 'sec', 100);
        $this->assertSame($a, $b);
    }

    public function test_different_secret_yields_different_sig(): void
    {
        $a = WebhookSignature::sign('payload', 'one', 100);
        $b = WebhookSignature::sign('payload', 'two', 100);
        $this->assertNotSame($a, $b);
    }
}
