<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Inventory ----
        Schema::create('acme_commerce_warehouses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->json('address_json')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('acme_commerce_stock_levels', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('acme_commerce_warehouses')->cascadeOnDelete();
            $table->unsignedInteger('on_hand')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->timestamps();
            $table->unique(['sku_id', 'warehouse_id']);
        });

        Schema::create('acme_commerce_stock_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('acme_commerce_warehouses')->cascadeOnDelete();
            // inbound | outbound | reserve | release | adjustment | transfer_in | transfer_out
            $table->string('type');
            $table->integer('quantity'); // signed; +receive, -ship
            $table->string('reference_type')->nullable(); // "order" | "return" | manual
            $table->ulid('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->foreignUlid('actor_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->index(['sku_id', 'warehouse_id', 'occurred_at'], 'cm_mov_sku_wh_at_idx');
            $table->index(['reference_type', 'reference_id']);
        });

        // ---- Returns (RMA) ----
        Schema::create('acme_commerce_returns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUlid('order_id')->constrained('acme_checkout_orders')->restrictOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('status')->default('requested'); // requested | approved | received | refunded | rejected
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('refund_amount_cents')->default(0);
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('acme_commerce_return_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('return_id')->constrained('acme_commerce_returns')->cascadeOnDelete();
            $table->foreignUlid('order_item_id')->constrained('acme_checkout_order_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('condition')->nullable(); // new | opened | damaged
            $table->text('reason')->nullable();
        });

        // ---- Reviews ----
        Schema::create('acme_commerce_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('acme_catalog_products')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->foreignUlid('order_id')->nullable()->constrained('acme_checkout_orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('pending'); // pending | approved | spam
            $table->timestamps();
            $table->index(['product_id', 'status', 'created_at']);
        });

        // ---- Campaigns ----
        Schema::create('acme_commerce_campaigns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('type'); // bxgy | bundle | timed_discount | freebie
            $table->json('rules_json');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'starts_at', 'ends_at']);
        });

        // ---- Loyalty points ----
        Schema::create('acme_commerce_loyalty_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained('acme_auth_users')->cascadeOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->unsignedBigInteger('lifetime_earned')->default(0);
            $table->timestamps();
        });

        Schema::create('acme_commerce_loyalty_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('account_id')->constrained('acme_commerce_loyalty_accounts')->cascadeOnDelete();
            $table->string('type'); // earn | redeem | expire | adjust
            $table->bigInteger('amount'); // signed
            $table->bigInteger('balance_after');
            $table->string('reference_type')->nullable();
            $table->ulid('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['account_id', 'created_at']);
            $table->index(['reference_type', 'reference_id'], 'cm_loyal_tx_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_commerce_loyalty_transactions');
        Schema::dropIfExists('acme_commerce_loyalty_accounts');
        Schema::dropIfExists('acme_commerce_campaigns');
        Schema::dropIfExists('acme_commerce_reviews');
        Schema::dropIfExists('acme_commerce_return_items');
        Schema::dropIfExists('acme_commerce_returns');
        Schema::dropIfExists('acme_commerce_stock_movements');
        Schema::dropIfExists('acme_commerce_stock_levels');
        Schema::dropIfExists('acme_commerce_warehouses');
    }
};
