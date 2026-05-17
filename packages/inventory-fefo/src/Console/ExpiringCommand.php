<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Console;

use Acme\InventoryFefo\Models\Batch;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Print batches expiring within the next N days. Useful as a
 * scheduled report or as input to "discount near-expiry" workflows.
 */
final class ExpiringCommand extends Command
{
    protected $signature   = 'acme:inventory-fefo:expiring {--days=30} {--include-expired}';
    protected $description = 'List batches expiring within the next N days.';

    public function handle(): int
    {
        $days  = max(1, (int) $this->option('days'));
        $today = CarbonImmutable::now()->toDateString();
        $until = CarbonImmutable::now()->addDays($days)->toDateString();

        $q = Batch::query()
            ->where('on_hand', '>', 0)
            ->where('expiry_date', '<=', $until);

        if (! $this->option('include-expired')) {
            $q->where('expiry_date', '>=', $today);
        }

        $rows = $q->orderBy('expiry_date')->get()->map(fn ($b) => [
            $b->expiry_date?->toDateString(),
            $b->sku_id,
            $b->warehouse_id,
            $b->lot_code ?? '—',
            $b->on_hand,
            $b->reserved,
            $b->available(),
        ])->all();

        if (! $rows) {
            $this->info("No batches expiring in {$days} day(s).");

            return self::SUCCESS;
        }

        $this->table(['expiry', 'sku', 'warehouse', 'lot', 'on_hand', 'reserved', 'available'], $rows);

        return self::SUCCESS;
    }
}
