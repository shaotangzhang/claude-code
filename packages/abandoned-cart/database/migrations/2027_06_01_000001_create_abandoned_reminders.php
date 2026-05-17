<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_abandoned_reminders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('cart_id')->constrained('acme_cart_carts')->cascadeOnDelete();
            $table->unsignedTinyInteger('round');
            $table->foreignUlid('coupon_id')->nullable()->constrained('acme_cart_coupons')->nullOnDelete();
            $table->timestamp('sent_at')->useCurrent();
            $table->unique(['cart_id', 'round']);
            $table->index(['cart_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_abandoned_reminders');
    }
};
