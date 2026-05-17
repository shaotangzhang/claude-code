<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A delivery zone matches by country + postal-code prefix.
        // Multiple speed tiers per zone: same-day, next-day, scheduled, ...
        Schema::create('acme_shipping_local_zones', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('country', 2);
            $table->json('postal_prefixes_json');             // ['100', '101', ...]
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'country']);
        });

        Schema::create('acme_shipping_local_rates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('zone_id')->constrained('acme_shipping_local_zones')->cascadeOnDelete();
            $table->string('key');                            // 'same-day' | 'next-day' | 'scheduled'
            $table->string('label');
            $table->unsignedBigInteger('cost_cents');
            $table->string('currency', 3);
            $table->unsignedBigInteger('min_subtotal_cents')->nullable();
            $table->unsignedSmallInteger('eta_minutes_min')->nullable();
            $table->unsignedSmallInteger('eta_minutes_max')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['zone_id', 'key', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_shipping_local_rates');
        Schema::dropIfExists('acme_shipping_local_zones');
    }
};
