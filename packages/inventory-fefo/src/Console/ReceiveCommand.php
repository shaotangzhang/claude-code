<?php

declare(strict_types=1);

namespace Acme\InventoryFefo\Console;

use Acme\Commerce\Models\StockLevel;
use Acme\InventoryFefo\Models\Batch;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Record an inbound batch and update the commerce StockLevel roll-up.
 *
 *   php artisan acme:inventory-fefo:receive <sku_id> <warehouse_id> \
 *       <qty> <yyyy-mm-dd> [--lot=CODE] [--supplier=REF]
 */
final class ReceiveCommand extends Command
{
    protected $signature   = 'acme:inventory-fefo:receive
                              {sku} {warehouse} {qty : units} {expiry : YYYY-MM-DD}
                              {--lot=} {--supplier=}';
    protected $description = 'Receive a new batch of stock with expiry date.';

    public function handle(): int
    {
        $skuId      = (string) $this->argument('sku');
        $warehouse  = (string) $this->argument('warehouse');
        $qty        = (int) $this->argument('qty');
        $expiry     = (string) $this->argument('expiry');
        $lot        = $this->option('lot');
        $supplier   = $this->option('supplier');

        if ($qty <= 0) {
            $this->error('quantity must be > 0');
            return self::FAILURE;
        }

        DB::transaction(function () use ($skuId, $warehouse, $qty, $expiry, $lot, $supplier): void {
            $batch = Batch::query()->where([
                ['sku_id', $skuId], ['warehouse_id', $warehouse],
                ['lot_code', $lot], ['expiry_date', $expiry],
            ])->lockForUpdate()->first();

            if ($batch) {
                $batch->on_hand += $qty;
                $batch->save();
            } else {
                Batch::create([
                    'sku_id'       => $skuId,
                    'warehouse_id' => $warehouse,
                    'lot_code'     => $lot,
                    'expiry_date'  => $expiry,
                    'on_hand'      => $qty,
                    'reserved'     => 0,
                    'received_at'  => CarbonImmutable::now()->toDateString(),
                    'supplier_ref' => $supplier,
                ]);
            }

            $level = StockLevel::query()
                ->where('sku_id', $skuId)->where('warehouse_id', $warehouse)
                ->lockForUpdate()->firstOrCreate(['sku_id' => $skuId, 'warehouse_id' => $warehouse]);
            $level->on_hand += $qty;
            $level->save();
        });

        $this->info("Received {$qty} unit(s) of {$skuId} into {$warehouse} (expiry {$expiry}).");

        return self::SUCCESS;
    }
}
