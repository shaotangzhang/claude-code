<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-SKU shipping data — kept here (not in catalog) so the
        // weight package can be removed cleanly without touching catalog.
        Schema::create('acme_shipping_sku_weights', function (Blueprint $table): void {
            $table->foreignUlid('sku_id')->primary()->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->unsignedInteger('weight_g')->default(0);
            $table->unsignedInteger('dim_w_mm')->nullable();
            $table->unsignedInteger('dim_h_mm')->nullable();
            $table->unsignedInteger('dim_d_mm')->nullable();
            $table->timestamps();
        });

        Schema::create('acme_shipping_weight_brackets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key');
            $table->string('label');
            $table->unsignedInteger('min_g')->default(0);
            $table->unsignedInteger('max_g')->nullable();  // null = +∞
            $table->unsignedBigInteger('cost_cents');
            $table->string('currency', 3);
            $table->unsignedSmallInteger('days_min')->nullable();
            $table->unsignedSmallInteger('days_max')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'min_g', 'max_g']);
            $table->unique(['key', 'currency', 'min_g']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_shipping_weight_brackets');
        Schema::dropIfExists('acme_shipping_sku_weights');
    }
};
