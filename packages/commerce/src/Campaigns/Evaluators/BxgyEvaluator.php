<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns\Evaluators;

use Acme\Commerce\Campaigns\RuleEvaluator;
use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartAdjustment;

/**
 * Buy X get Y: when at least `trigger_qty` of `trigger_sku_id` is in
 * the cart, `reward_qty` units of `reward_sku_id` get `reward_percent`%
 * off (default 100% = free).
 *
 * Implementation note: we don't insert a "free" line item. Instead we
 * emit a discount adjustment equal to the reward value already present
 * in the cart. The reward SKU must already be in the cart for the
 * discount to apply — this matches Amazon-style "add the free item
 * yourself to get the discount" mechanics and stays compatible with
 * cart's existing one-line-per-SKU model.
 *
 * rules_json shape:
 *   {
 *     "trigger_sku_id": "...",
 *     "trigger_qty": 2,
 *     "reward_sku_id": "...",
 *     "reward_qty": 1,
 *     "reward_percent": 100
 *   }
 */
final class BxgyEvaluator implements RuleEvaluator
{
    public function supports(Campaign $campaign): bool
    {
        return $campaign->type === Campaign::TYPE_BXGY;
    }

    public function evaluate(Campaign $campaign, array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        $rules    = $campaign->rules_json ?? [];
        $tSku     = (string) ($rules['trigger_sku_id'] ?? '');
        $tQty     = max(1, (int) ($rules['trigger_qty'] ?? 1));
        $rSku     = (string) ($rules['reward_sku_id']  ?? '');
        $rQty     = max(1, (int) ($rules['reward_qty'] ?? 1));
        $percent  = max(0, min(100, (int) ($rules['reward_percent'] ?? 100)));

        if ($tSku === '' || $rSku === '' || $percent === 0) {
            return [];
        }

        $triggerCount = 0;
        $rewardLine   = null;
        foreach ($items as $i) {
            if ($i['sku_id'] === $tSku) {
                $triggerCount += (int) $i['quantity'];
            }
            if ($i['sku_id'] === $rSku) {
                $rewardLine = $i;
            }
        }

        if ($triggerCount < $tQty || $rewardLine === null) {
            return [];
        }

        // How many times does the trigger condition fire?
        $triggers   = intdiv($triggerCount, $tQty);
        $rewardUnitsAvailable = (int) $rewardLine['quantity'];
        $rewardUnitsDiscount  = min($rewardUnitsAvailable, $triggers * $rQty);
        if ($rewardUnitsDiscount <= 0) {
            return [];
        }

        $unitPrice = (int) $rewardLine['unit_price_cents'];
        $amount    = -intdiv($rewardUnitsDiscount * $unitPrice * $percent, 100);

        return [new CartAdjustment(
            sourceKey:   "campaign:{$campaign->key}",
            description: "{$campaign->name} (Buy {$tQty} get {$rQty} @ {$percent}% off)",
            amountCents: $amount,
        )];
    }
}
