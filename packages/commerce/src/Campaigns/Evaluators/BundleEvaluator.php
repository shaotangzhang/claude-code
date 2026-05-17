<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns\Evaluators;

use Acme\Commerce\Campaigns\RuleEvaluator;
use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartAdjustment;

/**
 * Bundle: when every SKU in `required_sku_ids` is present in the cart
 * (each at least once), apply `discount_cents` off the subtotal.
 *
 * rules_json shape:
 *   { "required_sku_ids": ["s1","s2"], "discount_cents": 500 }
 */
final class BundleEvaluator implements RuleEvaluator
{
    public function supports(Campaign $campaign): bool
    {
        return $campaign->type === Campaign::TYPE_BUNDLE;
    }

    public function evaluate(Campaign $campaign, array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        $rules    = $campaign->rules_json ?? [];
        $required = (array) ($rules['required_sku_ids'] ?? []);
        $discount = (int) ($rules['discount_cents'] ?? 0);
        if (! $required || $discount <= 0) {
            return [];
        }

        $present = array_unique(array_column($items, 'sku_id'));
        foreach ($required as $skuId) {
            if (! in_array($skuId, $present, true)) {
                return [];
            }
        }

        $amount = -min($discount, $subtotalCents);

        return [new CartAdjustment(
            sourceKey:   "campaign:{$campaign->key}",
            description: $campaign->name,
            amountCents: $amount,
        )];
    }
}
