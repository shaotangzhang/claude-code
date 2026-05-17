<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Services;

use Acme\AbandonedCart\Events\CartAbandoned;
use Acme\AbandonedCart\Events\CartRecovered;
use Acme\AbandonedCart\Models\AbandonedReminder;
use Acme\Auth\Models\User;
use Acme\Cart\Models\Cart;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Detects abandonment + drives multi-round reminders + handles recovery.
 *
 * mark()         flips an active cart to abandoned and runs round 1
 *                (immediate reminder).
 * remind()       fires a subsequent round; mints a coupon per template;
 *                writes one AbandonedReminder audit row.
 * recover()      flips back to active when user clicks /recover/{token}.
 */
final class AbandonmentService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly CouponMinter $coupons,
    ) {}

    public function mark(Cart $cart): void
    {
        if ($cart->status !== Cart::STATUS_ACTIVE) {
            return;
        }

        $token = (string) ($cart->recovery_token ?? '');
        if ($token === '') {
            $token = (string) Str::ulid() . bin2hex(random_bytes(8));
        }

        DB::transaction(function () use ($cart, $token): void {
            $cart->recovery_token = $token;
            $cart->abandoned_at   = CarbonImmutable::now();
            $cart->reminded_at    = CarbonImmutable::now();
            $cart->status         = Cart::STATUS_ABANDONED;
            $cart->save();
        });

        $this->fireRound($cart, round: 1);
    }

    public function remind(Cart $cart, int $round): void
    {
        if ($cart->status !== Cart::STATUS_ABANDONED) {
            return;
        }
        // Idempotency: skip if this round already sent.
        if (AbandonedReminder::query()->where('cart_id', $cart->id)->where('round', $round)->exists()) {
            return;
        }

        $this->fireRound($cart, $round);
    }

    public function recover(string $token, ?string $authUserId): Cart
    {
        $cart = Cart::query()->where('recovery_token', $token)->first()
            ?? throw new RuntimeException('Unknown recovery token.');

        if ($cart->status === Cart::STATUS_CONVERTED) {
            throw new RuntimeException('Cart already converted to an order.');
        }
        if ($cart->status === Cart::STATUS_MERGED) {
            throw new RuntimeException('Cart was merged into another and cannot be recovered.');
        }

        $ttl = (int) config('acme.abandoned-cart.token_ttl_hours', 72);
        if ($cart->abandoned_at && $cart->abandoned_at->copy()->addHours($ttl)->isPast()) {
            throw new RuntimeException('Recovery link expired.');
        }

        DB::transaction(function () use ($cart, $authUserId): void {
            $cart->status         = Cart::STATUS_ACTIVE;
            $cart->abandoned_at   = null;
            $cart->recovery_token = null;
            if ($authUserId && ! $cart->user_id) {
                $cart->user_id     = $authUserId;
                $cart->guest_token = null;
            }
            $cart->save();
        });

        $this->events->dispatch(new CartRecovered($cart->id, $cart->user_id));

        return $cart;
    }

    private function fireRound(Cart $cart, int $round): void
    {
        $template = config("acme.abandoned-cart.rounds.{$round}");
        if (! is_array($template)) {
            return;
        }

        $couponRow  = null;
        $couponCode = null;
        if (is_array($template['coupon'] ?? null)) {
            $couponRow  = $this->coupons->mint((string) $cart->currency, $template['coupon']);
            $couponCode = $couponRow->code;
        }

        DB::transaction(function () use ($cart, $round, $couponRow): void {
            AbandonedReminder::create([
                'cart_id'   => $cart->id,
                'round'     => $round,
                'coupon_id' => $couponRow?->id,
                'sent_at'   => CarbonImmutable::now(),
            ]);
            $cart->reminded_at = CarbonImmutable::now();
            $cart->save();
        });

        [$email, $itemCount, $totalCents] = $this->snapshot($cart);

        $this->events->dispatch(new CartAbandoned(
            cartId:        $cart->id,
            userId:        $cart->user_id,
            email:         $email,
            recoveryToken: (string) $cart->recovery_token,
            recoveryUrl:   url(config('acme.abandoned-cart.route_prefix', 'cart') . '/recover/' . $cart->recovery_token),
            itemCount:     $itemCount,
            totalCents:    $totalCents,
            currency:      (string) $cart->currency,
            round:         $round,
            couponCode:    $couponCode,
        ));
    }

    /** @return array{0:?string,1:int,2:int} */
    private function snapshot(Cart $cart): array
    {
        $cart->loadMissing('items');
        $email = null;
        if ($cart->user_id) {
            $email = User::query()->where('id', $cart->user_id)->value('email');
        }
        $itemCount  = (int) $cart->items->where('is_gift', false)->sum('quantity');
        $totalCents = (int) ($cart->total_cents ?: 0);

        return [$email, $itemCount, $totalCents];
    }
}
