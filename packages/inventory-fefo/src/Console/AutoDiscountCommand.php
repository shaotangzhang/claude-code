<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Console;

use Acme\Commerce\Models\Campaign;
use Acme\InventoryFefo\Models\Batch;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * For every batch expiring within N days, ensure a TIMED_DISCOUNT
 * campaign exists that targets that SKU. Idempotent on
 * (sku, expiry_date) — the campaign key encodes both, so re-runs are
 * no-ops if the campaign already exists.
 *
 *   php artisan acme:inventory-fefo:auto-discount --days=14 --percent=20
 *
 * Schedule it daily.
 */
final class AutoDiscountCommand extends Command
{
    protected $signature   = 'acme:inventory-fefo:auto-discount
                              {--days=14 : create discount if batch expires within N days}
                              {--percent=20 : discount percent}
                              {--dry-run}';
    protected $description = 'Auto-create timed_discount campaigns for SKUs whose stock is approaching expiry.';

    public function handle(): int
    {
        $days    = max(1, (int) $this->option('days'));
        $percent = max(1, min(100, (int) $this->option('percent')));
        $dry     = (bool) $this->option('dry-run');
        $today   = CarbonImmutable::now()->startOfDay();
        $cutoff  = $today->copy()->addDays($days);

        // Group at-risk inventory by (sku_id, expiry_date) — one campaign per pair.
        $rows = Batch::query()
            ->where('on_hand', '>', 0)
            ->where('expiry_date', '>=', $today->toDateString())
            ->where('expiry_date', '<=', $cutoff->toDateString())
            ->get(['sku_id', 'expiry_date']);

        $pairs = [];
        foreach ($rows as $r) {
            $key = $r->sku_id . '|' . $r->expiry_date->toDateString();
            $pairs[$key] = ['sku_id' => $r->sku_id, 'expiry_date' => $r->expiry_date];
        }

        $created = 0; $skipped = 0;
        foreach ($pairs as $pair) {
            $sku    = $pair['sku_id'];
            $expiry = $pair['expiry_date'];
            $key    = "near-expiry:{$sku}:" . $expiry->toDateString();

            if (Campaign::query()->where('key', $key)->exists()) {
                $skipped++;
                continue;
            }

            if ($dry) {
                $this->line("[dry] create campaign {$key} ({$percent}% off, ends {$expiry->toDateString()})");
                $created++;
                continue;
            }

            Campaign::create([
                'key'        => $key,
                'name'       => "Near-expiry · {$sku} (–{$percent}%)",
                'type'       => Campaign::TYPE_TIMED_DISCOUNT,
                'rules_json' => [
                    'scope'   => 'sku',
                    'sku_ids' => [$sku],
                    'percent' => $percent,
                ],
                'starts_at'  => $today,
                'ends_at'    => $expiry,
                'active'     => true,
            ]);
            $created++;
        }

        $this->info(($dry ? '[dry] ' : '') . "Created {$created} campaign(s), {$skipped} already existed.");

        return self::SUCCESS;
    }
}
