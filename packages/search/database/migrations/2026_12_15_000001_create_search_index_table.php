<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_search_index', function (Blueprint $table): void {
            $table->foreignUlid('product_id')->primary()->constrained('acme_catalog_products')->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('title');
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->text('searchable_text');                   // denormalized: title + brand + tags + summary + ...
            $table->unsignedBigInteger('min_price_cents')->nullable();
            $table->unsignedBigInteger('max_price_cents')->nullable();
            $table->json('attrs_json')->nullable();
            $table->timestamp('indexed_at');
            $table->index(['locale', 'category']);
            $table->index(['locale', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_search_index');
    }
};
