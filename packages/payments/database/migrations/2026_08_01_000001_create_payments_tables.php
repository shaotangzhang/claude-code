<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_payments_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('gateway');                 // "manual" | "stripe" | ...
            $table->string('related_type');            // "order" | "subscription" | ...
            $table->ulid('related_id');
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3);
            $table->string('status')->default('pending'); // pending | succeeded | failed | refunded
            $table->string('gateway_reference')->nullable(); // id at the gateway (charge/intent id)
            $table->string('failure_reason')->nullable();
            $table->json('payload_json')->nullable();   // anything else worth retaining
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['related_type', 'related_id']);
            $table->index(['gateway', 'gateway_reference']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_payments_transactions');
    }
};
