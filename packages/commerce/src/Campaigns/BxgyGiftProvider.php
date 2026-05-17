<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns;

use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartGiftInsert;
use Acme\Contracts\Commerce\CartGiftProvider;

/**
 * The line-insert side of BxGy campaigns. When the trigger condition is
 * met AND the reward SKU is NOT already in the cart, this provider
 * pushes the reward into the cart as a gift line.
 *
 * The companion BxgyEvaluator handles the case where the reward IS in
 * the cart — there it emits a discount equal to the reward's existing
 * value. Together they cover both UX flows transparently.
 *
 * Only runs for fully-free rewards (reward_percent=100). Partial-off
 * rewards still require the user to add the SKU themselves and use
 * BxgyEvaluator's discount path.
 */
final class BxgyGiftProvider implements CartGiftProvider
{
    public function giftsFor(array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        $out = [];

        foreach ($this->liveBxgyCampaigns() as $campaign) {
            $rules = $campaign->rules_json ?? [];
            $tSku  = (string) ($rules['trigger_sku_id'] ?? '');
            $tQty  = max(1, (int) ($rules['trigger_qty'] ?? 1));
            $rSku  = (string) ($rules['reward_sku_id']  ?? '');
            $rQty  = max(1, (int) ($rules['reward_qty']  ?? 1));
            $pct   = max(0, min(100, (int) ($rules['reward_percent'] ?? 100)));

            if ($tSku === '' || $rSku === '' || $pct !== 100) {
                continue; // only fully-free rewards become gifts
            }

            $triggerCount   = 0;
            $rewardInCart   = false;
            foreach ($items as $i) {
                if ($i['sku_id'] === $tSku) { $triggerCount += (int) $i['quantity']; }
                if ($i['sku_id'] === $rSku) { $rewardInCart  = true; }
            }
            if ($triggerCount < $tQty || $rewardInCart) {
                continue; // either not triggered, or reward already in cart → discount path handles it
            }

            $triggers = intdiv($triggerCount, $tQty);
            $gifts    = $triggers * $rQty;
            if ($gifts <= 0) { continue; }

            $out[] = new CartGiftInsert(
                sourceKey:   "campaign:{$campaign->key}:gift",
                description: "{$campaign->name} (free gift)",
                skuId:       $rSku,
                quantity:    $gifts,
            );
        }

        return $out;
    }

    /** @return iterable<Campaign> */
    private function liveBxgyCampaigns(): iterable
    {
        return Campaign::query()
            ->where('type', Campaign::TYPE_BXGY)
            ->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->get();
    }
}
