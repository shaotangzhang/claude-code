<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acme_cart_items', function (Blueprint $table): void {
            $table->boolean('is_gift')->default(false)->after('attrs_json');
            $table->string('gift_source_key')->nullable()->after('is_gift');
            $table->index(['cart_id', 'is_gift']);
            $table->index('gift_source_key');
        });
    }

    public function down(): void
    {
        Schema::table('acme_cart_items', function (Blueprint $table): void {
            $table->dropIndex(['cart_id', 'is_gift']);
            $table->dropIndex(['gift_source_key']);
            $table->dropColumn(['is_gift', 'gift_source_key']);
        });
    }
};
