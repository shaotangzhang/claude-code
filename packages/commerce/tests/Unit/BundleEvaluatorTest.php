<?php

declare(strict_types=1);

namespace Acme\Commerce\Tests\Unit;

use Acme\Commerce\Campaigns\Evaluators\BundleEvaluator;
use Acme\Commerce\Models\Campaign;
use PHPUnit\Framework\TestCase;

final class BundleEvaluatorTest extends TestCase
{
    public function test_supports_only_bundle_type(): void
    {
        $eval = new BundleEvaluator();
        $this->assertTrue($eval->supports($this->campaign(['type' => Campaign::TYPE_BUNDLE])));
        $this->assertFalse($eval->supports($this->campaign(['type' => Campaign::TYPE_TIMED_DISCOUNT])));
    }

    public function test_no_match_when_required_sku_missing(): void
    {
        $eval = new BundleEvaluator();
        $items = [
            ['sku_id' => 'a', 'quantity' => 1, 'unit_price_cents' => 1000, 'line_total_cents' => 1000, 'currency' => 'USD'],
        ];
        $adj = $eval->evaluate(
            $this->campaign(['rules_json' => ['required_sku_ids' => ['a', 'b'], 'discount_cents' => 500]]),
            $items, 1000, 'USD', null,
        );
        $this->assertSame([], $adj);
    }

    public function test_fires_when_all_required_skus_present(): void
    {
        $eval = new BundleEvaluator();
        $items = [
            ['sku_id' => 'a', 'quantity' => 1, 'unit_price_cents' => 1000, 'line_total_cents' => 1000, 'currency' => 'USD'],
            ['sku_id' => 'b', 'quantity' => 2, 'unit_price_cents' => 500,  'line_total_cents' => 1000, 'currency' => 'USD'],
        ];
        $adj = $eval->evaluate(
            $this->campaign([
                'key'  => 'summer',
                'name' => 'Summer bundle',
                'rules_json' => ['required_sku_ids' => ['a', 'b'], 'discount_cents' => 500],
            ]),
            $items, 2000, 'USD', null,
        );
        $this->assertCount(1, $adj);
        $this->assertSame(-500, $adj[0]->amountCents);
        $this->assertSame('campaign:summer', $adj[0]->sourceKey);
    }

    public function test_caps_discount_at_subtotal(): void
    {
        $eval = new BundleEvaluator();
        $items = [
            ['sku_id' => 'a', 'quantity' => 1, 'unit_price_cents' => 100, 'line_total_cents' => 100, 'currency' => 'USD'],
        ];
        $adj = $eval->evaluate(
            $this->campaign([
                'key' => 'k', 'name' => 'n',
                'rules_json' => ['required_sku_ids' => ['a'], 'discount_cents' => 10_000],
            ]),
            $items, 100, 'USD', null,
        );
        $this->assertSame(-100, $adj[0]->amountCents);
    }

    private function campaign(array $attrs): Campaign
    {
        $c = new Campaign();
        $c->type       = $attrs['type'] ?? Campaign::TYPE_BUNDLE;
        $c->key        = $attrs['key']  ?? 'k';
        $c->name       = $attrs['name'] ?? 'name';
        $c->rules_json = $attrs['rules_json'] ?? [];

        return $c;
    }
}
