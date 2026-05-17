<?php

declare(strict_types=1);

namespace Acme\PaymentsStripe\Tests\Unit;

use Acme\PaymentsStripe\StripeSignature;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StripeSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_abcdef';

    public function test_accepts_valid_signature(): void
    {
        $body = '{"hello":"world"}';
        $ts   = 1_700_000_000;
        $sig  = hash_hmac('sha256', $ts . '.' . $body, self::SECRET);
        $header = "t={$ts},v1={$sig}";

        StripeSignature::verify($body, $header, self::SECRET, toleranceSeconds: 300, now: $ts);

        $this->assertTrue(true); // no throw
    }

    public function test_rejects_tampered_body(): void
    {
        $body = '{"hello":"world"}';
        $ts   = 1_700_000_000;
        $sig  = hash_hmac('sha256', $ts . '.' . $body, self::SECRET);
        $header = "t={$ts},v1={$sig}";

        $this->expectException(RuntimeException::class);
        StripeSignature::verify('{"hello":"WORLD"}', $header, self::SECRET, now: $ts);
    }

    public function test_rejects_stale_timestamp(): void
    {
        $body = '{}';
        $ts   = 1_700_000_000;
        $sig  = hash_hmac('sha256', $ts . '.' . $body, self::SECRET);
        $header = "t={$ts},v1={$sig}";

        $this->expectException(RuntimeException::class);
        StripeSignature::verify($body, $header, self::SECRET, toleranceSeconds: 60, now: $ts + 3600);
    }

    public function test_rejects_missing_v1_entry(): void
    {
        $this->expectException(RuntimeException::class);
        StripeSignature::verify('{}', 't=123,v0=abc', self::SECRET, now: 123);
    }

    public function test_accepts_when_one_of_many_v1_matches(): void
    {
        $body = '{}';
        $ts   = 1_700_000_000;
        $good = hash_hmac('sha256', $ts . '.' . $body, self::SECRET);
        $header = "t={$ts},v1=dead,v1={$good}";

        StripeSignature::verify($body, $header, self::SECRET, now: $ts);
        $this->assertTrue(true);
    }
}
