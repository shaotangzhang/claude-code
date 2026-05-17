<?php

declare(strict_types=1);

namespace Acme\Commerce\Services;

use Acme\Commerce\Events\StockLow;
use Acme\Commerce\Events\StockReserved;
use Acme\Commerce\Models\StockLevel;
use Acme\Commerce\Models\StockMovement;
use Acme\Commerce\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * All stock mutations go through here. Each call writes a StockMovement
 * audit row + adjusts the StockLevel atomically; available = on_hand - reserved.
 *
 * Pick strategy is naïve: first warehouse with capacity. Replace by
 * binding a different StockService implementation in your host SP.
 */
final class StockService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function receive(string $skuId, string $warehouseId, int $quantity, ?string $reason = null): StockMovement
    {
        return $this->mutate($skuId, $warehouseId, StockMovement::TYPE_INBOUND, $quantity, fn (StockLevel $l) => $l->on_hand += $quantity, reason: $reason);
    }

    public function adjust(string $skuId, string $warehouseId, int $signedQuantity, ?string $reason = null): StockMovement
    {
        return $this->mutate(
            $skuId, $warehouseId, StockMovement::TYPE_ADJUSTMENT, $signedQuantity,
            fn (StockLevel $l) => $l->on_hand = max(0, $l->on_hand + $signedQuantity),
            reason: $reason,
        );
    }

    /**
     * Reserve quantities for an order, choosing the first warehouse with
     * enough available stock for each line. Returns true if every line
     * was reserved; throws otherwise (with no partial reservation).
     *
     * @param  array<string,int>  $lines  sku_id => qty
     */
    public function reserveForOrder(string $orderId, array $lines): bool
    {
        DB::transaction(function () use ($orderId, $lines): void {
            foreach ($lines as $skuId => $qty) {
                $remaining = $qty;
                $levels    = StockLevel::query()->where('sku_id', $skuId)
                    ->lockForUpdate()->get();
                foreach ($levels as $l) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, max(0, $l->on_hand - $l->reserved));
                    if ($take <= 0) continue;
                    $l->reserved += $take;
                    $l->save();
                    $this->writeMovement($skuId, $l->warehouse_id, StockMovement::TYPE_RESERVE, $take, 'order', $orderId);
                    $remaining -= $take;
                }
                if ($remaining > 0) {
                    throw new RuntimeException("Insufficient stock for SKU {$skuId} (short by {$remaining}).");
                }
            }
        });

        $this->events->dispatch(new StockReserved($orderId, $lines));

        return true;
    }

    /** Convert a previous reserve to an actual outbound (decrement on_hand). */
    public function shipForOrder(string $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $reservations = StockMovement::query()
                ->where('reference_type', 'order')->where('reference_id', $orderId)
                ->where('type', StockMovement::TYPE_RESERVE)->get();

            foreach ($reservations->groupBy(['sku_id', 'warehouse_id']) as $skuId => $byWh) {
                foreach ($byWh as $whId => $rows) {
                    $qty   = (int) $rows->sum('quantity');
                    $level = StockLevel::query()->where('sku_id', $skuId)->where('warehouse_id', $whId)
                        ->lockForUpdate()->first();
                    if (! $level || $qty <= 0) continue;

                    $level->reserved = max(0, $level->reserved - $qty);
                    $level->on_hand  = max(0, $level->on_hand - $qty);
                    $level->save();

                    $this->writeMovement($skuId, $whId, StockMovement::TYPE_OUTBOUND, -$qty, 'order', $orderId);
                    $this->maybeEmitLow($level);
                }
            }
        });
    }

    public function releaseForOrder(string $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $reservations = StockMovement::query()
                ->where('reference_type', 'order')->where('reference_id', $orderId)
                ->where('type', StockMovement::TYPE_RESERVE)->get();

            foreach ($reservations->groupBy(['sku_id', 'warehouse_id']) as $skuId => $byWh) {
                foreach ($byWh as $whId => $rows) {
                    $qty   = (int) $rows->sum('quantity');
                    $level = StockLevel::query()->where('sku_id', $skuId)->where('warehouse_id', $whId)
                        ->lockForUpdate()->first();
                    if (! $level || $qty <= 0) continue;

                    $level->reserved = max(0, $level->reserved - $qty);
                    $level->save();

                    $this->writeMovement($skuId, $whId, StockMovement::TYPE_RELEASE, -$qty, 'order', $orderId);
                }
            }
        });
    }

    private function mutate(
        string $skuId, string $warehouseId, string $type, int $signedQty,
        callable $apply, ?string $reason = null,
    ): StockMovement {
        return DB::transaction(function () use ($skuId, $warehouseId, $type, $signedQty, $apply, $reason): StockMovement {
            Warehouse::query()->whereKey($warehouseId)->firstOrFail();
            $level = StockLevel::query()->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
                ->lockForUpdate()->firstOrCreate(['sku_id' => $skuId, 'warehouse_id' => $warehouseId]);

            $apply($level);
            $level->save();

            $m = $this->writeMovement($skuId, $warehouseId, $type, $signedQty, null, null, $reason);
            $this->maybeEmitLow($level);

            return $m;
        });
    }

    private function writeMovement(
        string $skuId, string $warehouseId, string $type, int $qty,
        ?string $refType, ?string $refId, ?string $reason = null,
    ): StockMovement {
        return StockMovement::create([
            'sku_id'         => $skuId,
            'warehouse_id'   => $warehouseId,
            'type'           => $type,
            'quantity'       => $qty,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'reason'         => $reason,
            'occurred_at'    => CarbonImmutable::now(),
        ]);
    }

    private function maybeEmitLow(StockLevel $level): void
    {
        $threshold = config('acme.commerce.inventory.low_threshold');
        if ($threshold === null) return;
        if ($level->available() <= (int) $threshold) {
            $this->events->dispatch(new StockLow($level->sku_id, $level->warehouse_id, $level->available(), (int) $threshold));
        }
    }
}
