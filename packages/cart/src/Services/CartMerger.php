<?php

declare(strict_types=1);

namespace Acme\Cart\Services;

use Acme\Cart\Events\CartMerged;
use Acme\Cart\Models\Cart;
use Acme\Cart\Models\CartItem;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Merges a guest cart into a user-owned cart on login. The guest cart
 * is marked "merged" and never returned by findOrCreate again.
 *
 * Strategy: for each guest item, increment the matching user-cart line
 * if SKU & attrs_json match, otherwise add a new line. If currencies
 * differ we keep the user cart and skip the guest cart (rare; user's
 * existing currency wins).
 */
final class CartMerger
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly TotalsCalculator $totals,
    ) {}

    public function merge(string $userId, string $guestToken): ?Cart
    {
        return DB::transaction(function () use ($userId, $guestToken): ?Cart {
            $guest = Cart::query()->where('guest_token', $guestToken)
                ->where('status', Cart::STATUS_ACTIVE)->with('items')->first();
            if (! $guest) {
                return null;
            }

            $user = Cart::query()->where('user_id', $userId)
                ->where('status', Cart::STATUS_ACTIVE)->with('items')->first();

            if (! $user) {
                $guest->user_id     = $userId;
                $guest->guest_token = null;
                $guest->save();
                $this->totals->recalculate($guest->fresh(['items', 'coupons']));
                $this->events->dispatch(new CartMerged($guest->id, null, $userId));

                return $guest;
            }

            if ($guest->currency !== $user->currency) {
                $guest->status = Cart::STATUS_MERGED;
                $guest->save();

                return $user;
            }

            foreach ($guest->items as $g) {
                $existing = $user->items->firstWhere('sku_id', $g->sku_id);
                if ($existing && $existing->attrs_json === $g->attrs_json) {
                    $existing->quantity        += $g->quantity;
                    $existing->line_total_cents = $existing->unit_price_cents * $existing->quantity;
                    $existing->save();
                } else {
                    CartItem::create([
                        'cart_id'          => $user->id,
                        'sku_id'           => $g->sku_id,
                        'quantity'         => $g->quantity,
                        'unit_price_cents' => $g->unit_price_cents,
                        'line_total_cents' => $g->line_total_cents,
                        'currency'         => $g->currency,
                        'attrs_json'       => $g->attrs_json,
                    ]);
                }
            }

            $guest->status = Cart::STATUS_MERGED;
            $guest->save();

            $this->totals->recalculate($user->fresh(['items', 'coupons']));
            $this->events->dispatch(new CartMerged($user->id, $guest->id, $userId));

            return $user;
        });
    }
}
