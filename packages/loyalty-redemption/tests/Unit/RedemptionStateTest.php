<?php

declare(strict_types=1);

namespace Acme\LoyaltyRedemption\Tests\Unit;

use Acme\Cart\Models\Cart;
use Acme\LoyaltyRedemption\Support\RedemptionState;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test on the meta_json accessor — no DB hit since Cart model
 * is not saved. We exercise the in-memory shape only.
 */
final class RedemptionStateTest extends TestCase
{
    public function test_get_returns_null_when_meta_missing(): void
    {
        $cart           = new Cart();
        $cart->currency = 'USD';
        $this->assertNull(RedemptionState::get($cart));
    }

    public function test_get_returns_null_when_shape_wrong(): void
    {
        $cart           = new Cart();
        $cart->currency = 'USD';
        $cart->meta_json = ['loyalty_redemption' => ['points' => 100]]; // missing amount
        $this->assertNull(RedemptionState::get($cart));
    }

    public function test_get_normalises_types(): void
    {
        $cart           = new Cart();
        $cart->currency = 'USD';
        $cart->meta_json = ['loyalty_redemption' => [
            'points' => '500', 'amount_cents' => '500', 'currency' => 'USD',
        ]];

        $s = RedemptionState::get($cart);
        $this->assertNotNull($s);
        $this->assertSame(500, $s['points']);
        $this->assertSame(500, $s['amount_cents']);
        $this->assertSame('USD', $s['currency']);
    }
}
