<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns\Evaluators;

use Acme\Commerce\Campaigns\RuleEvaluator;
use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartAdjustment;

/**
 * Freebie (a.k.a. "free shipping over X") campaign. Currently supports
 * one variant: zero out shipping when cart subtotal ≥ a threshold.
 *
 * "Free gift with purchase" (auto-add a SKU as a free line) is harder —
 * it requires line-insert semantics in the cart pipeline — and is
 * deferred to a future cart 0.5 cycle.
 *
 * rules_json shape:
 *   { "kind": "free_shipping", "min_subtotal_cents": 10000 }
 */
final class FreebieEvaluator implements RuleEvaluator
{
    public function supports(Campaign $campaign): bool
    {
        return $campaign->type === Campaign::TYPE_FREEBIE;
    }

    public function evaluate(Campaign $campaign, array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        $rules = $campaign->rules_json ?? [];
        $kind  = (string) ($rules['kind'] ?? 'free_shipping');

        if ($kind !== 'free_shipping') {
            return []; // future kinds: 'gift_sku', 'gift_with_purchase'
        }

        $min = (int) ($rules['min_subtotal_cents'] ?? 0);
        if ($subtotalCents < $min) {
            return [];
        }

        return [new CartAdjustment(
            sourceKey:   "campaign:{$campaign->key}",
            description: "{$campaign->name} (free shipping)",
            amountCents: 0,                                  // ignored for this target
            target:      CartAdjustment::TARGET_SHIPPING_FREE,
        )];
    }
}
