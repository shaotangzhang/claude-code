<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns\Evaluators;

use Acme\Commerce\Campaigns\RuleEvaluator;
use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartAdjustment;

/**
 * Timed discount: percentage off line totals of SKUs matching scope.
 * Window is enforced by Campaign::isLiveNow() — this evaluator only
 * runs for campaigns that are already live.
 *
 * rules_json shape:
 *   { "scope": "cart"|"sku", "sku_ids": [...], "percent": 20 }
 */
final class TimedDiscountEvaluator implements RuleEvaluator
{
    public function supports(Campaign $campaign): bool
    {
        return $campaign->type === Campaign::TYPE_TIMED_DISCOUNT;
    }

    public function evaluate(Campaign $campaign, array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        $rules   = $campaign->rules_json ?? [];
        $percent = max(0, min(100, (int) ($rules['percent'] ?? 0)));
        if ($percent === 0) {
            return [];
        }
        $scope   = (string) ($rules['scope'] ?? 'cart');
        $targetSkus = array_flip((array) ($rules['sku_ids'] ?? []));

        if ($scope === 'sku' && $targetSkus) {
            $matchingSubtotal = 0;
            foreach ($items as $i) {
                if (isset($targetSkus[$i['sku_id']])) {
                    $matchingSubtotal += $i['line_total_cents'];
                }
            }
            if ($matchingSubtotal <= 0) {
                return [];
            }
            $amount = -intdiv($matchingSubtotal * $percent, 100);
        } else {
            // scope=cart: percent off the entire subtotal
            $amount = -intdiv($subtotalCents * $percent, 100);
        }

        if ($amount === 0) {
            return [];
        }

        return [new CartAdjustment(
            sourceKey:   "campaign:{$campaign->key}",
            description: "{$campaign->name} ({$percent}% off)",
            amountCents: $amount,
        )];
    }
}
