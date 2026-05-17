<?php

declare(strict_types=1);

namespace Acme\Cart\Services;

use Acme\Cart\Events\ItemAdded;
use Acme\Cart\Events\ItemRemoved;
use Acme\Cart\Events\ItemUpdated;
use Acme\Cart\Models\Cart;
use Acme\Cart\Models\CartItem;
use Acme\Catalog\Models\Sku;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * High-level cart write API. All mutating methods recalc totals inside
 * the same transaction so the cart row never lies about its state.
 */
final class CartService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly TotalsCalculator $totals,
    ) {}

    public function findOrCreate(?string $userId, ?string $guestToken, string $currency, string $locale = 'en'): Cart
    {
        if ($userId) {
            $cart = Cart::query()->where('user_id', $userId)->where('status', Cart::STATUS_ACTIVE)->first();
            if ($cart) {
                return $cart;
            }
        }
        if ($guestToken) {
            $cart = Cart::query()->where('guest_token', $guestToken)->where('status', Cart::STATUS_ACTIVE)->first();
            if ($cart) {
                if ($userId && ! $cart->user_id) {
                    $cart->user_id = $userId;
                    $cart->save();
                }
                return $cart;
            }
        }

        return Cart::create([
            'user_id'     => $userId,
            'guest_token' => $userId ? null : ($guestToken ?? (string) Str::ulid()),
            'currency'    => $currency,
            'locale'      => $locale,
            'status'      => Cart::STATUS_ACTIVE,
        ]);
    }

    public function addItem(Cart $cart, Sku $sku, int $quantity = 1, array $attrs = []): CartItem
    {
        $this->guardCurrency($cart, $sku);
        $this->guardQuantity($quantity);

        return DB::transaction(function () use ($cart, $sku, $quantity, $attrs): CartItem {
            $item = CartItem::query()->where('cart_id', $cart->id)->where('sku_id', $sku->id)->first();
            if ($item) {
                $item->quantity         = min($item->quantity + $quantity, (int) config('acme.cart.max_quantity_per_line', 999));
                $item->line_total_cents = $item->unit_price_cents * $item->quantity;
                $item->save();
            } else {
                $item = CartItem::create([
                    'cart_id'          => $cart->id,
                    'sku_id'           => $sku->id,
                    'quantity'         => $quantity,
                    'unit_price_cents' => $sku->price_cents,
                    'line_total_cents' => $sku->price_cents * $quantity,
                    'currency'         => $sku->currency,
                    'attrs_json'       => $attrs ?: null,
                ]);
            }

            $this->totals->recalculate($cart->fresh(['items', 'coupons']));
            $this->events->dispatch(new ItemAdded($cart->id, $item->id, $sku->id, $quantity));

            return $item;
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): CartItem
    {
        $this->guardQuantity($quantity);

        return DB::transaction(function () use ($item, $quantity): CartItem {
            if ($quantity === 0) {
                $cartId = $item->cart_id;
                $item->delete();
                $this->totals->recalculate(Cart::query()->with(['items', 'coupons'])->findOrFail($cartId));
                $this->events->dispatch(new ItemRemoved($cartId, $item->id));

                return $item;
            }

            $item->quantity         = $quantity;
            $item->line_total_cents = $item->unit_price_cents * $quantity;
            $item->save();

            $this->totals->recalculate($item->cart->fresh(['items', 'coupons']));
            $this->events->dispatch(new ItemUpdated($item->cart_id, $item->id, $quantity));

            return $item;
        });
    }

    public function removeItem(CartItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $cartId = $item->cart_id;
            $item->delete();
            $this->totals->recalculate(Cart::query()->with(['items', 'coupons'])->findOrFail($cartId));
            $this->events->dispatch(new ItemRemoved($cartId, $item->id));
        });
    }

    public function markConverted(Cart $cart): void
    {
        $cart->status = Cart::STATUS_CONVERTED;
        $cart->save();
    }

    private function guardCurrency(Cart $cart, Sku $sku): void
    {
        if ($sku->currency !== $cart->currency) {
            throw new RuntimeException(
                "Currency mismatch: cart={$cart->currency}, sku={$sku->currency}. Start a new cart for that currency.",
            );
        }
    }

    private function guardQuantity(int $q): void
    {
        $max = (int) config('acme.cart.max_quantity_per_line', 999);
        if ($q < 0 || $q > $max) {
            throw new RuntimeException("Quantity must be between 0 and {$max}.");
        }
    }
}
