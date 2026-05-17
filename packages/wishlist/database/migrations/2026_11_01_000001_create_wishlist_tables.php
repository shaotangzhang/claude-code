<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_wishlist_lists', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('acme_auth_users')->cascadeOnDelete();
            $table->string('name')->default('Wishlist');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('acme_wishlist_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('list_id')->constrained('acme_wishlist_lists')->cascadeOnDelete();
            $table->foreignUlid('sku_id')->constrained('acme_catalog_skus')->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('added_at')->useCurrent();
            $table->unique(['list_id', 'sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_wishlist_items');
        Schema::dropIfExists('acme_wishlist_lists');
    }
};
