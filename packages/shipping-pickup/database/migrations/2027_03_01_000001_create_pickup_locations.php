<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_shipping_pickup_locations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('country', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('line1')->nullable();
            $table->string('phone')->nullable();
            $table->string('hours')->nullable();
            $table->unsignedSmallInteger('ready_days_min')->default(1);
            $table->unsignedSmallInteger('ready_days_max')->default(3);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_shipping_pickup_locations');
    }
};
