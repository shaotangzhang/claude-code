<?php

declare(strict_types=1);

namespace Acme\PaymentsAlipay\Tests\Unit;

use Acme\PaymentsAlipay\AlipaySignature;
use PHPUnit\Framework\TestCase;

final class AlipaySignatureTest extends TestCase
{
    public function test_canonical_string_sorts_and_drops_empties_and_sign(): void
    {
        $s = AlipaySignature::canonicalString([
            'b' => '2',
            'a' => '1',
            'empty' => '',
            'nullv' => null,
            'sign' => 'IGNORED',
            'sign_type' => 'IGNORED',
            'c' => '3',
        ]);
        $this->assertSame('a=1&b=2&c=3', $s);
    }

    public function test_array_value_is_json_encoded(): void
    {
        $s = AlipaySignature::canonicalString(['biz' => ['k' => 'v']]);
        $this->assertSame('biz={"k":"v"}', $s);
    }

    public function test_sign_verify_round_trip(): void
    {
        // Generate ephemeral RSA keypair just for this test.
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($res);
        openssl_pkey_export($res, $privatePem);
        $publicPem = openssl_pkey_get_details($res)['key'];

        $params = ['app_id' => 'X1', 'method' => 'alipay.trade.query', 'out_trade_no' => 'tx-9'];
        $sig    = AlipaySignature::sign($params, $privatePem);

        $this->assertNotEmpty($sig);
        $this->assertTrue(AlipaySignature::verify($params, $sig, $publicPem));
    }

    public function test_tampered_payload_fails_verification(): void
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privatePem);
        $publicPem = openssl_pkey_get_details($res)['key'];

        $params = ['out_trade_no' => 'tx-9', 'total_amount' => '10.00'];
        $sig    = AlipaySignature::sign($params, $privatePem);

        $tampered = $params; $tampered['total_amount'] = '0.01';
        $this->assertFalse(AlipaySignature::verify($tampered, $sig, $publicPem));
    }
}
