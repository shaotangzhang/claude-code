<?php

declare(strict_types=1);

namespace Acme\Commerce\Campaigns;

use Acme\Commerce\Models\Campaign;
use Acme\Contracts\Commerce\CartAdjustment;

/**
 * One RuleEvaluator per Campaign::TYPE_*. Each is pure — given the cart
 * snapshot and the campaign config, returns the adjustments to apply.
 * Empty list = the campaign doesn't fire on this cart.
 */
interface RuleEvaluator
{
    public function supports(Campaign $campaign): bool;

    /**
     * @param  array<int,array{sku_id:string,quantity:int,unit_price_cents:int,line_total_cents:int,currency:string,attrs?:array<string,mixed>}>  $items
     * @return list<CartAdjustment>
     */
    public function evaluate(
        Campaign $campaign,
        array $items,
        int $subtotalCents,
        string $currency,
        ?string $userId,
    ): array;
}
