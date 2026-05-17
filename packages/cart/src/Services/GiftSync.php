<?php

declare(strict_types=1);

namespace Acme\Cart\Services;

use Acme\Cart\Adjustments\GiftRegistry;
use Acme\Cart\Models\Cart;
use Acme\Cart\Models\CartItem;
use Acme\Contracts\Commerce\PriceResolver;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles desired gifts (from CartGiftProviders) against the
 * is_gift=true rows already in the cart. Idempotent:
 *
 *   for each (sourceKey, skuId) the providers want
 *     ensure a row exists; update quantity if it shifted
 *   for each existing gift line whose sourceKey is no longer desired
 *     delete it
 *
 * Run BEFORE TotalsCalculator's subtotal/discount math so the rest of
 * the pipeline sees the right set of lines.
 */
final class GiftSync
{
    public function __construct(
        private readonly GiftRegistry $registry,
        private readonly PriceResolver $prices,
    ) {}

    public function reconcile(Cart $cart): void
    {
        if ($this->registry->all() === []) {
            return;
        }

        $cart->loadMissing(['items']);
        $nonGift = $cart->items->where('is_gift', false)->values();
        $existing = $cart->items->where('is_gift', true)->values();

        $providerItems = $nonGift->map(fn ($i) => [
            'sku_id'           => $i->sku_id,
            'quantity'         => $i->quantity,
            'unit_price_cents' => $i->unit_price_cents,
            'line_total_cents' => $i->line_total_cents,
            'currency'         => $i->currency,
            'attrs'            => $i->attrs_json ?? [],
        ])->all();
        $subtotal = (int) $nonGift->sum('line_total_cents');

        // Gather desired (sourceKey => [skuId => quantity])
        $desired = [];
        foreach ($this->registry->all() as $provider) {
            foreach ($provider->giftsFor($providerItems, $subtotal, $cart->currency, $cart->user_id) as $g) {
                $desired[$g->sourceKey][$g->skuId] = ($desired[$g->sourceKey][$g->skuId] ?? 0) + $g->quantity;
            }
        }

        DB::transaction(function () use ($cart, $existing, $desired): void {
            $seen = [];
            foreach ($desired as $sourceKey => $bySku) {
                foreach ($bySku as $skuId => $qty) {
                    $unit = $this->prices->priceFor($skuId, $cart->currency);
                    if ($unit === null || $qty <= 0) {
                        continue; // can't price → skip silently
                    }

                    $row = $existing->first(fn ($i) => $i->gift_source_key === $sourceKey && $i->sku_id === $skuId);
                    if ($row) {
                        if ($row->quantity !== $qty || $row->unit_price_cents !== $unit) {
                            $row->quantity         = $qty;
                            $row->unit_price_cents = $unit;
                            $row->line_total_cents = $unit * $qty;
                            $row->save();
                        }
                    } else {
                        CartItem::create([
                            'cart_id'          => $cart->id,
                            'sku_id'           => $skuId,
                            'quantity'         => $qty,
                            'unit_price_cents' => $unit,
                            'line_total_cents' => $unit * $qty,
                            'currency'         => $cart->currency,
                            'is_gift'          => true,
                            'gift_source_key'  => $sourceKey,
                        ]);
                    }
                    $seen[] = "{$sourceKey}|{$skuId}";
                }
            }

            // Reap orphan gifts whose sourceKey/sku is no longer requested.
            foreach ($existing as $row) {
                $token = "{$row->gift_source_key}|{$row->sku_id}";
                if (! in_array($token, $seen, true)) {
                    $row->delete();
                }
            }
        });
    }
}
