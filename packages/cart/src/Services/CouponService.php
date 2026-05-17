<?php

declare(strict_types=1);

namespace Acme\Cart\Services;

use Acme\Cart\Events\CouponApplied;
use Acme\Cart\Events\CouponRemoved;
use Acme\Cart\Models\Cart;
use Acme\Cart\Models\Coupon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CouponService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly TotalsCalculator $totals,
    ) {}

    public function apply(Cart $cart, string $code): Coupon
    {
        $coupon = Coupon::query()->where('code', $code)->first()
            ?? throw new RuntimeException("Unknown coupon: {$code}");

        if (! $coupon->isUsableNow()) {
            throw new RuntimeException("Coupon {$code} is not usable now.");
        }
        if ($coupon->currency !== null && $coupon->currency !== $cart->currency) {
            throw new RuntimeException("Coupon currency mismatch: cart={$cart->currency}, coupon={$coupon->currency}");
        }
        if ($coupon->min_subtotal_cents && $cart->subtotal_cents < $coupon->min_subtotal_cents) {
            throw new RuntimeException("Subtotal below coupon minimum.");
        }

        DB::transaction(function () use ($cart, $coupon): void {
            $cart->coupons()->syncWithoutDetaching([
                $coupon->id => ['applied_amount_cents' => 0, 'applied_at' => now()],
            ]);
            $this->totals->recalculate($cart);
        });

        $this->events->dispatch(new CouponApplied($cart->id, $coupon->id, $coupon->code));

        return $coupon;
    }

    public function remove(Cart $cart, Coupon $coupon): void
    {
        DB::transaction(function () use ($cart, $coupon): void {
            $cart->coupons()->detach($coupon->id);
            $this->totals->recalculate($cart);
        });

        $this->events->dispatch(new CouponRemoved($cart->id, $coupon->id, $coupon->code));
    }
}
