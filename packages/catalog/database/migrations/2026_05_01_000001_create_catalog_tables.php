<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_catalog_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')->nullable()->constrained('acme_catalog_categories')->nullOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('locale')->default('en');
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('acme_catalog_brands', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('locale')->default('en');
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('acme_catalog_products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->nullable()->constrained('acme_catalog_categories')->nullOnDelete();
            $table->foreignUlid('brand_id')->nullable()->constrained('acme_catalog_brands')->nullOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('locale')->default('en');
            $table->string('status')->default('draft'); // draft | published | archived
            $table->json('attrs_json')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['locale', 'slug']);
            $table->index(['status']);
        });

        Schema::create('acme_catalog_skus', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('acme_catalog_products')->cascadeOnDelete();
            $table->string('code');                        // SKU code
            $table->unsignedBigInteger('price_cents');     // current displayed price
            $table->unsignedBigInteger('list_price_cents')->nullable(); // crossed-out / MSRP
            $table->string('currency', 3)->default('USD');
            $table->json('attrs_json')->nullable();        // { color: red, size: L }
            $table->string('stock_label')->nullable();     // display-only: "In stock" / "Pre-order" / "Out of stock"
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'code']);
            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_catalog_skus');
        Schema::dropIfExists('acme_catalog_products');
        Schema::dropIfExists('acme_catalog_brands');
        Schema::dropIfExists('acme_catalog_categories');
    }
};
