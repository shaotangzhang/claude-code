<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Services;

use Acme\AbandonedCart\Events\CartAbandoned;
use Acme\AbandonedCart\Events\CartRecovered;
use Acme\Auth\Models\User;
use Acme\Cart\Models\Cart;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Detects abandonment + handles recovery.
 *
 * mark()  is called by TickCommand for each eligible cart. It assigns
 *         status=abandoned, stamps abandoned_at, mints a recovery token
 *         (idempotent — re-running on the same cart re-emits with the
 *         same token unless it has been recovered).
 *
 * recover() is called by RecoveryController when a user clicks the
 *         link. It flips status back to active, clears abandoned_at,
 *         and optionally attaches the cart to the authenticated user.
 *         Refuses tokens past their TTL.
 */
final class AbandonmentService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function mark(Cart $cart): void
    {
        if ($cart->status !== Cart::STATUS_ACTIVE) {
            return; // idempotent: already converted/merged/abandoned → skip
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

        [$email, $itemCount, $totalCents] = $this->snapshot($cart);

        $this->events->dispatch(new CartAbandoned(
            cartId:        $cart->id,
            userId:        $cart->user_id,
            email:         $email,
            recoveryToken: $token,
            recoveryUrl:   url(config('acme.abandoned-cart.route_prefix', 'cart') . '/recover/' . $token),
            itemCount:     $itemCount,
            totalCents:    $totalCents,
            currency:      (string) $cart->currency,
        ));
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
