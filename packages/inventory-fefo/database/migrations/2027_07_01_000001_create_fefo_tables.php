<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_inventory_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('acme_commerce_warehouses')->cascadeOnDelete();
            $table->string('lot_code')->nullable();
            $table->date('expiry_date');
            $table->unsignedInteger('on_hand')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->date('received_at')->nullable();
            $table->string('supplier_ref')->nullable();
            $table->timestamps();
            $table->index(['sku_id', 'warehouse_id', 'expiry_date']);
            $table->unique(['sku_id', 'warehouse_id', 'lot_code', 'expiry_date'], 'fefo_uniq');
        });

        // Per-reservation audit: which batches contributed how many units
        // to an order. Lets us undo precisely on release/cancel.
        Schema::create('acme_inventory_allocations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('acme_inventory_batches')->cascadeOnDelete();
            $table->string('reference_type');                  // 'order'
            $table->ulid('reference_id');
            $table->unsignedInteger('quantity');
            $table->string('state')->default('reserved');     // reserved | shipped | released
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->index(['reference_type', 'reference_id', 'state'], 'fefo_alloc_ref_state_idx');
            $table->index(['batch_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_inventory_allocations');
        Schema::dropIfExists('acme_inventory_batches');
    }
};
