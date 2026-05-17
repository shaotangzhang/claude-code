<?php

declare(strict_types=1);

namespace Acme\InventoryFefo;

use Acme\Commerce\Models\StockLevel;
use Acme\Contracts\Commerce\StockAllocator;
use Acme\InventoryFefo\Models\Allocation;
use Acme\InventoryFefo\Models\Batch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Allocate stock by shortest-expiry batch first (FEFO). Each line:
 *
 *   batches = WHERE sku=? AND warehouse=? AND expiry > today
 *             ORDER BY expiry_date ASC, received_at ASC
 *
 *   walk allocating min(remaining_qty, batch.available) per batch
 *   until line fulfilled or candidates exhausted (throw).
 *
 * commerce's StockLevel (the per-(sku,warehouse) summary) is kept in
 * sync as a denormalized roll-up so its tests + admin reads still work.
 *
 * Bind this class to the StockAllocator contract in the host SP to
 * activate FEFO platform-wide:
 *
 *   $this->app->singleton(StockAllocator::class, FefoStockAllocator::class);
 *
 * Done automatically by InventoryFefoServiceProvider.
 */
final class FefoStockAllocator implements StockAllocator
{
    public function reserveForOrder(string $orderId, array $lines): bool
    {
        DB::transaction(function () use ($orderId, $lines): void {
            $today = CarbonImmutable::now()->toDateString();

            foreach ($lines as $skuId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $remaining = $qty;
                $batches = Batch::query()
                    ->where('sku_id', $skuId)
                    ->where('expiry_date', '>=', $today)
                    ->orderBy('expiry_date')->orderBy('received_at')
                    ->lockForUpdate()->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, $batch->available());
                    if ($take <= 0) continue;

                    $batch->reserved += $take;
                    $batch->save();

                    Allocation::create([
                        'batch_id'       => $batch->id,
                        'reference_type' => 'order',
                        'reference_id'   => $orderId,
                        'quantity'       => $take,
                        'state'          => Allocation::STATE_RESERVED,
                        'reserved_at'    => CarbonImmutable::now(),
                    ]);

                    $this->bumpSummary($skuId, $batch->warehouse_id, reservedDelta: $take);

                    $remaining -= $take;
                }

                if ($remaining > 0) {
                    throw new RuntimeException("FEFO: insufficient non-expired stock for SKU {$skuId} (short {$remaining}).");
                }
            }
        });

        return true;
    }

    public function shipForOrder(string $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $allocs = Allocation::query()
                ->where('reference_type', 'order')->where('reference_id', $orderId)
                ->where('state', Allocation::STATE_RESERVED)
                ->lockForUpdate()->get();

            foreach ($allocs as $alloc) {
                $batch = Batch::query()->lockForUpdate()->find($alloc->batch_id);
                if (! $batch) continue;

                $batch->reserved = max(0, $batch->reserved - $alloc->quantity);
                $batch->on_hand  = max(0, $batch->on_hand  - $alloc->quantity);
                $batch->save();

                $alloc->state      = Allocation::STATE_SHIPPED;
                $alloc->shipped_at = CarbonImmutable::now();
                $alloc->save();

                $this->bumpSummary(
                    $batch->sku_id, $batch->warehouse_id,
                    onHandDelta: -$alloc->quantity, reservedDelta: -$alloc->quantity,
                );
            }
        });
    }

    public function releaseForOrder(string $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $allocs = Allocation::query()
                ->where('reference_type', 'order')->where('reference_id', $orderId)
                ->where('state', Allocation::STATE_RESERVED)
                ->lockForUpdate()->get();

            foreach ($allocs as $alloc) {
                $batch = Batch::query()->lockForUpdate()->find($alloc->batch_id);
                if (! $batch) continue;

                $batch->reserved = max(0, $batch->reserved - $alloc->quantity);
                $batch->save();

                $alloc->state       = Allocation::STATE_RELEASED;
                $alloc->released_at = CarbonImmutable::now();
                $alloc->save();

                $this->bumpSummary($batch->sku_id, $batch->warehouse_id, reservedDelta: -$alloc->quantity);
            }
        });
    }

    /** Keep commerce's StockLevel roll-up consistent with batch totals. */
    private function bumpSummary(string $skuId, string $warehouseId, int $onHandDelta = 0, int $reservedDelta = 0): void
    {
        $level = StockLevel::query()
            ->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
            ->lockForUpdate()->firstOrCreate(['sku_id' => $skuId, 'warehouse_id' => $warehouseId]);
        $level->on_hand  = max(0, $level->on_hand  + $onHandDelta);
        $level->reserved = max(0, $level->reserved + $reservedDelta);
        $level->save();
    }
}
