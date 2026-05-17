<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns;

use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartAdjustmentProvider;

/**
 * The single CartAdjustmentProvider registered with cart on behalf of
 * the entire commerce campaign system. It loads every live campaign,
 * dispatches to the matching RuleEvaluator, and accumulates adjustments.
 *
 * Pure: no DB writes, only reads campaigns.
 */
final class CampaignProvider implements CartAdjustmentProvider
{
    /** @param iterable<RuleEvaluator> $evaluators */
    public function __construct(private readonly iterable $evaluators) {}

    public function adjustmentsFor(array $items, int $subtotalCents, string $currency, ?string $userId): array
    {
        if ($items === [] || $subtotalCents <= 0) {
            return [];
        }

        $campaigns = Campaign::query()
            ->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->get();

        $out = [];
        foreach ($campaigns as $campaign) {
            foreach ($this->evaluators as $eval) {
                if (! $eval->supports($campaign)) {
                    continue;
                }
                foreach ($eval->evaluate($campaign, $items, $subtotalCents, $currency, $userId) as $adj) {
                    $out[] = $adj;
                }
            }
        }

        return $out;
    }
}
