<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_shipping_zones', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('acme_shipping_zone_countries', function (Blueprint $table): void {
            $table->foreignUlid('zone_id')->constrained('acme_shipping_zones')->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->primary(['zone_id', 'country_code']);
            $table->index('country_code');
        });

        Schema::create('acme_shipping_zone_rates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('zone_id')->constrained('acme_shipping_zones')->cascadeOnDelete();
            $table->string('key');                              // 'standard' | 'express' | ...
            $table->string('label');
            $table->unsignedBigInteger('cost_cents');
            $table->string('currency', 3);
            $table->unsignedBigInteger('min_subtotal_cents')->nullable();
            $table->unsignedBigInteger('max_subtotal_cents')->nullable();
            $table->unsignedSmallInteger('days_min')->nullable();
            $table->unsignedSmallInteger('days_max')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['zone_id', 'key', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_shipping_zone_rates');
        Schema::dropIfExists('acme_shipping_zone_countries');
        Schema::dropIfExists('acme_shipping_zones');
    }
};
