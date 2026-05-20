<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Console;

use Acme\Commerce\Models\StockLevel;
use Acme\InventoryFefo\Models\Batch;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Move stock between warehouses, batch-aware.
 *
 *   php artisan acme:inventory-fefo:transfer <sku> <from-wh> <to-wh> <qty>
 *
 * Algorithm: walk batches in source warehouse FEFO-order, allocate qty
 * across them; for each chunk drawn from source.batch (sku, lot, expiry),
 * find-or-create a matching batch in dest warehouse and add the units
 * there. Source loses on_hand; dest gains on_hand. StockLevel rollups
 * adjusted on both sides. Reservations are NOT moved — caller should
 * ensure reserved counts are zero or accept that the source might go
 * negative-available.
 */
final class TransferCommand extends Command
{
    protected $signature   = 'acme:inventory-fefo:transfer
                              {sku} {from} {to} {qty : units}
                              {--reason=}';
    protected $description = 'Transfer units of a SKU between two warehouses, FEFO-ordered.';

    public function handle(): int
    {
        $skuId   = (string) $this->argument('sku');
        $fromWh  = (string) $this->argument('from');
        $toWh    = (string) $this->argument('to');
        $qty     = (int) $this->argument('qty');
        $reason  = $this->option('reason');

        if ($fromWh === $toWh) {
            $this->error('Source and destination warehouses must differ.');
            return self::FAILURE;
        }
        if ($qty <= 0) {
            $this->error('Quantity must be positive.');
            return self::FAILURE;
        }

        $moved = 0;
        DB::transaction(function () use ($skuId, $fromWh, $toWh, $qty, $reason, &$moved): void {
            $today = CarbonImmutable::now()->toDateString();

            $sources = Batch::query()
                ->where('sku_id', $skuId)
                ->where('warehouse_id', $fromWh)
                ->where('expiry_date', '>=', $today)
                ->orderBy('expiry_date')->orderBy('received_at')
                ->lockForUpdate()->get();

            $remaining = $qty;
            foreach ($sources as $src) {
                if ($remaining <= 0) break;
                $available = max(0, $src->on_hand - $src->reserved);
                $take = min($remaining, $available);
                if ($take <= 0) continue;

                // Find or create matching destination batch.
                $dest = Batch::query()
                    ->where('sku_id', $skuId)
                    ->where('warehouse_id', $toWh)
                    ->where('lot_code', $src->lot_code)
                    ->where('expiry_date', $src->expiry_date)
                    ->lockForUpdate()->first();

                if (! $dest) {
                    $dest = Batch::create([
                        'sku_id'       => $skuId,
                        'warehouse_id' => $toWh,
                        'lot_code'     => $src->lot_code,
                        'expiry_date'  => $src->expiry_date,
                        'on_hand'      => 0,
                        'reserved'     => 0,
                        'received_at'  => CarbonImmutable::now()->toDateString(),
                        'supplier_ref' => $src->supplier_ref,
                    ]);
                }

                $src->on_hand  = max(0, $src->on_hand - $take);
                $src->save();
                $dest->on_hand = $dest->on_hand + $take;
                $dest->save();

                $this->adjustSummary($skuId, $fromWh, -$take);
                $this->adjustSummary($skuId, $toWh,   +$take);

                $remaining -= $take;
                $moved     += $take;
            }

            if ($remaining > 0) {
                throw new \RuntimeException(
                    "Insufficient stock to transfer (short {$remaining} of {$qty}).",
                );
            }
        });

        $this->info("Transferred {$moved} unit(s) of {$skuId}: {$fromWh} → {$toWh}" . ($reason ? " ({$reason})" : '') . '.');

        return self::SUCCESS;
    }

    private function adjustSummary(string $skuId, string $warehouseId, int $delta): void
    {
        $level = StockLevel::query()
            ->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
            ->lockForUpdate()->firstOrCreate(['sku_id' => $skuId, 'warehouse_id' => $warehouseId]);
        $level->on_hand = max(0, $level->on_hand + $delta);
        $level->save();
    }
}
