<?php

declare(strict_types=1);

namespace Acme\SkuBundles\Services;

use Acme\Cart\Models\Cart;
use Acme\Cart\Models\CartItem;
use Acme\Cart\Services\TotalsCalculator;
use Acme\Contracts\Commerce\PriceResolver;
use Acme\SkuBundles\Events\BundleAddedToCart;
use Acme\SkuBundles\Models\Bundle;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Add a Bundle to a Cart by inserting its child SKUs as individual
 * cart_items, all tagged with the same `bundle_source_key`. The bundle
 * discount itself is applied by BundleAdjustmentProvider on recalc.
 *
 * sourceKey shape: "bundle:<bundle.key>:<ulid-short>" — a single user
 * can add the same bundle twice (each gets a fresh sourceKey).
 */
final class BundleService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly TotalsCalculator $totals,
        private readonly PriceResolver $prices,
    ) {}

    public function addToCart(Cart $cart, Bundle $bundle): string
    {
        if (! $bundle->active) {
            throw new RuntimeException("Bundle {$bundle->key} is inactive.");
        }
        if ($bundle->currency !== $cart->currency) {
            throw new RuntimeException("Bundle currency {$bundle->currency} does not match cart {$cart->currency}.");
        }

        $bundle->loadMissing('items');
        if ($bundle->items->isEmpty()) {
            throw new RuntimeException("Bundle {$bundle->key} has no items.");
        }

        $sourceKey = "bundle:{$bundle->key}:" . strtolower(substr((string) Str::ulid(), -8));

        DB::transaction(function () use ($cart, $bundle, $sourceKey): void {
            foreach ($bundle->items as $sku) {
                $qty  = (int) $sku->pivot->quantity;
                $unit = $this->prices->priceFor($sku->id, $cart->currency);
                if ($unit === null) {
                    throw new RuntimeException("No price for SKU {$sku->id} in {$cart->currency}.");
                }

                CartItem::create([
                    'cart_id'           => $cart->id,
                    'sku_id'            => $sku->id,
                    'quantity'          => $qty,
                    'unit_price_cents'  => $unit,
                    'line_total_cents'  => $unit * $qty,
                    'currency'          => $cart->currency,
                    'is_gift'           => false,
                    'bundle_source_key' => $sourceKey,
                ]);
            }
        });

        $this->totals->recalculate($cart->fresh(['items', 'coupons']));

        $this->events->dispatch(new BundleAddedToCart(
            cartId:         $cart->id,
            bundleKey:      $bundle->key,
            sourceKey:      $sourceKey,
            childLineCount: (int) $bundle->items->count(),
        ));

        return $sourceKey;
    }

    public function removeFromCart(Cart $cart, string $sourceKey): void
    {
        DB::transaction(function () use ($cart, $sourceKey): void {
            CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('bundle_source_key', $sourceKey)
                ->delete();
        });

        $this->totals->recalculate($cart->fresh(['items', 'coupons']));
    }
}
