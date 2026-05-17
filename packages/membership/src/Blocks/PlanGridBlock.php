<?php

declare(strict_types=1);

namespace Acme\Membership\Blocks;

use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;
use Acme\Membership\Models\Plan;

/**
 * Pricing-page block — renders active plans grouped by tier.
 * data: { tier_keys?: string[] }  filter to specific tiers; else all
 */
final class PlanGridBlock extends AbstractBlock
{
    public static function key(): string { return 'membership.plans'; }

    public static function label(): string { return 'Membership · Plans'; }

    public static function icon(): ?string { return 'award'; }

    public function render(array $data, RenderContext $context): string
    {
        $q = Plan::query()->with('tier')->where('active', true);
        if (! empty($data['tier_keys']) && is_array($data['tier_keys'])) {
            $q->whereHas('tier', fn ($t) => $t->whereIn('key', $data['tier_keys']));
        }
        $plans = $q->get()->sortBy(fn ($p) => [$p->tier->level, $p->price_cents])->values();

        return $this->view('acme-membership::blocks.plans', ['plans' => $plans], $context);
    }
}
