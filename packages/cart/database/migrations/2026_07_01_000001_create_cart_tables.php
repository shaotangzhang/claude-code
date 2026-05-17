<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_cart_carts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('guest_token', 80)->nullable()->unique();
            $table->string('currency', 3);
            $table->string('locale')->default('en');
            $table->string('status')->default('active'); // active|abandoned|merged|converted
            // Denormalized totals (in cents). Recomputed by TotalsCalculator.
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('shipping_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('acme_cart_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('cart_id')->constrained('acme_cart_carts')->cascadeOnDelete();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_total_cents');
            $table->string('currency', 3);
            $table->json('attrs_json')->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'sku_id']);
        });

        Schema::create('acme_cart_coupons', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('type'); // percent | fixed
            $table->unsignedInteger('value'); // percent 1-100 OR cents
            $table->string('currency', 3)->nullable();
            $table->unsignedBigInteger('min_subtotal_cents')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });

        Schema::create('acme_cart_cart_coupons', function (Blueprint $table): void {
            $table->foreignUlid('cart_id')->constrained('acme_cart_carts')->cascadeOnDelete();
            $table->foreignUlid('coupon_id')->constrained('acme_cart_coupons')->cascadeOnDelete();
            $table->unsignedBigInteger('applied_amount_cents')->default(0);
            $table->timestamp('applied_at')->useCurrent();
            $table->primary(['cart_id', 'coupon_id']);
        });

        Schema::create('acme_cart_coupon_uses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('coupon_id')->constrained('acme_cart_coupons')->cascadeOnDelete();
            $table->foreignUlid('cart_id')->nullable()->constrained('acme_cart_carts')->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->timestamp('used_at')->useCurrent();
            $table->index(['coupon_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_cart_coupon_uses');
        Schema::dropIfExists('acme_cart_cart_coupons');
        Schema::dropIfExists('acme_cart_coupons');
        Schema::dropIfExists('acme_cart_items');
        Schema::dropIfExists('acme_cart_carts');
    }
};
