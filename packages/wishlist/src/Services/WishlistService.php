<?php

declare(strict_types=1);

namespace Acme\Wishlist\Services;

use Acme\Cart\Models\Cart;
use Acme\Cart\Services\CartService;
use Acme\Catalog\Models\Sku;
use Acme\Wishlist\Events\WishlistItemAdded;
use Acme\Wishlist\Events\WishlistItemMovedToCart;
use Acme\Wishlist\Models\WishlistItem;
use Acme\Wishlist\Models\WishlistList;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Wishlist write API. Lists are created lazily — first add for a user
 * spawns the default list. Adds are idempotent on (list, sku).
 */
final class WishlistService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly CartService $carts,
    ) {}

    public function defaultListFor(string $userId): WishlistList
    {
        return WishlistList::firstOrCreate(
            ['user_id' => $userId, 'is_default' => true],
            ['name' => 'Wishlist'],
        );
    }

    public function addItem(string $userId, Sku $sku, ?WishlistList $list = null, ?string $note = null): WishlistItem
    {
        $list ??= $this->defaultListFor($userId);
        if ($list->user_id !== $userId) {
            throw new RuntimeException("List {$list->id} does not belong to user {$userId}.");
        }
        $max = (int) config('acme.wishlist.max_items_per_list', 0);
        if ($max > 0 && $list->items()->count() >= $max) {
            throw new RuntimeException("Wishlist '{$list->name}' is full (max {$max}).");
        }

        return DB::transaction(function () use ($list, $sku, $note, $userId): WishlistItem {
            $existing = WishlistItem::query()->where('list_id', $list->id)->where('sku_id', $sku->id)->first();
            if ($existing) {
                if ($note !== null) {
                    $existing->note = $note;
                    $existing->save();
                }
                return $existing;
            }

            $item = WishlistItem::create([
                'list_id'  => $list->id,
                'sku_id'   => $sku->id,
                'note'     => $note,
                'added_at' => CarbonImmutable::now(),
            ]);

            $this->events->dispatch(new WishlistItemAdded($list->id, $item->id, $userId, $sku->id));

            return $item;
        });
    }

    public function removeItem(WishlistItem $item, string $userId): void
    {
        if ($item->list?->user_id !== $userId) {
            throw new RuntimeException("Cannot remove someone else's wishlist item.");
        }
        $item->delete();
    }

    /** Move a wishlist item to the user's active cart, optionally keeping it on the wishlist. */
    public function moveToCart(WishlistItem $item, string $userId, Cart $cart, int $quantity = 1, bool $keepInWishlist = false): void
    {
        if ($item->list?->user_id !== $userId) {
            throw new RuntimeException("Cannot move someone else's wishlist item.");
        }
        $sku = $item->sku ?? throw new RuntimeException("Wishlist item refers to a missing SKU.");

        $this->carts->addItem($cart, $sku, $quantity);

        if (! $keepInWishlist) {
            $item->delete();
        }

        $this->events->dispatch(new WishlistItemMovedToCart(
            userId:   $userId,
            skuId:    $sku->id,
            quantity: $quantity,
            cartId:   $cart->id,
        ));
    }
}
