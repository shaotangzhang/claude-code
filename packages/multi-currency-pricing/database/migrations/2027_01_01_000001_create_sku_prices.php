<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_pricing_sku_prices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->string('currency', 3);
            $table->unsignedBigInteger('price_cents');
            $table->unsignedBigInteger('list_price_cents')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['sku_id', 'currency']);
            $table->index(['currency', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_pricing_sku_prices');
    }
};
