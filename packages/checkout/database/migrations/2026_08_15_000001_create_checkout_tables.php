<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_checkout_orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('currency', 3);
            $table->string('status'); // pending_payment | paid | fulfilled | canceled | refunded | failed_payment
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('shipping_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('shipping_option_key')->nullable();
            $table->string('payment_gateway')->nullable();   // chosen at submit
            $table->ulid('payment_transaction_id')->nullable(); // FK to payments
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('payment_transaction_id');
        });

        Schema::create('acme_checkout_order_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('acme_checkout_orders')->cascadeOnDelete();
            $table->foreignUlid('sku_id')->nullable()->constrained('acme_catalog_skus')->nullOnDelete();
            $table->string('sku_code');             // snapshot — survives sku deletion
            $table->string('product_title');         // snapshot
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_total_cents');
            $table->string('currency', 3);
            $table->json('attrs_json')->nullable();
            $table->index('order_id');
        });

        Schema::create('acme_checkout_invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('acme_checkout_orders')->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('draft'); // draft | issued | paid | void
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_checkout_invoices');
        Schema::dropIfExists('acme_checkout_order_items');
        Schema::dropIfExists('acme_checkout_orders');
    }
};
