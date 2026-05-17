<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_bundles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();             // 'summer-pack'
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_cents');   // bundled price
            $table->string('currency', 3);
            $table->string('locale')->default('en');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['locale', 'slug']);
            $table->index(['active', 'currency']);
        });

        Schema::create('acme_bundle_items', function (Blueprint $table): void {
            $table->foreignUlid('bundle_id')->constrained('acme_bundles')->cascadeOnDelete();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->primary(['bundle_id', 'sku_id']);
        });

        // Per-cart "this bundle is currently in your cart" marker.
        Schema::table('acme_cart_items', function (Blueprint $table): void {
            $table->string('bundle_source_key')->nullable()->after('gift_source_key');
            $table->index('bundle_source_key');
        });
    }

    public function down(): void
    {
        Schema::table('acme_cart_items', function (Blueprint $table): void {
            $table->dropIndex(['bundle_source_key']);
            $table->dropColumn('bundle_source_key');
        });
        Schema::dropIfExists('acme_bundle_items');
        Schema::dropIfExists('acme_bundles');
    }
};
