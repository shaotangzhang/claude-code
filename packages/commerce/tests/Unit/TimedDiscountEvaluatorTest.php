<?php

declare(strict_types=1);

namespace Acme\Commerce\Tests\Unit;

use Acme\Commerce\Campaigns\Evaluators\TimedDiscountEvaluator;
use Acme\Commerce\Models\Campaign;
use PHPUnit\Framework\TestCase;

final class TimedDiscountEvaluatorTest extends TestCase
{
    public function test_cart_scope_applies_to_subtotal(): void
    {
        $items = [
            ['sku_id' => 'a', 'quantity' => 1, 'unit_price_cents' => 1000, 'line_total_cents' => 1000, 'currency' => 'USD'],
        ];
        $adj = (new TimedDiscountEvaluator())->evaluate(
            $this->campaign(['rules_json' => ['scope' => 'cart', 'percent' => 20]]),
            $items, 1000, 'USD', null,
        );
        $this->assertSame(-200, $adj[0]->amountCents);
    }

    public function test_sku_scope_filters_lines(): void
    {
        $items = [
            ['sku_id' => 'target',  'quantity' => 1, 'unit_price_cents' => 1000, 'line_total_cents' => 1000, 'currency' => 'USD'],
            ['sku_id' => 'ignore',  'quantity' => 1, 'unit_price_cents' => 500,  'line_total_cents' => 500,  'currency' => 'USD'],
        ];
        $adj = (new TimedDiscountEvaluator())->evaluate(
            $this->campaign(['rules_json' => ['scope' => 'sku', 'sku_ids' => ['target'], 'percent' => 10]]),
            $items, 1500, 'USD', null,
        );
        $this->assertCount(1, $adj);
        $this->assertSame(-100, $adj[0]->amountCents);
    }

    public function test_zero_percent_no_op(): void
    {
        $adj = (new TimedDiscountEvaluator())->evaluate(
            $this->campaign(['rules_json' => ['percent' => 0]]),
            [['sku_id' => 'a', 'quantity' => 1, 'unit_price_cents' => 100, 'line_total_cents' => 100, 'currency' => 'USD']],
            100, 'USD', null,
        );
        $this->assertSame([], $adj);
    }

    public function test_clamps_percent_to_0_100(): void
    {
        $adj = (new TimedDiscountEvaluator())->evaluate(
            $this->campaign(['rules_json' => ['scope' => 'cart', 'percent' => 9999]]),
            [['sku_id' => 'a', 'quantity' => 1, 'unit_price_cents' => 1000, 'line_total_cents' => 1000, 'currency' => 'USD']],
            1000, 'USD', null,
        );
        $this->assertSame(-1000, $adj[0]->amountCents); // 100% of subtotal
    }

    private function campaign(array $attrs): Campaign
    {
        $c = new Campaign();
        $c->type       = Campaign::TYPE_TIMED_DISCOUNT;
        $c->key        = 'k';
        $c->name       = 'n';
        $c->rules_json = $attrs['rules_json'] ?? [];

        return $c;
    }
}
