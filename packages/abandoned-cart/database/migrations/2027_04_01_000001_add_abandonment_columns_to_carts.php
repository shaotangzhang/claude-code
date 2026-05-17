<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acme_cart_carts', function (Blueprint $table): void {
            $table->string('recovery_token', 80)->nullable()->unique()->after('meta_json');
            $table->timestamp('abandoned_at')->nullable()->after('recovery_token');
            $table->timestamp('reminded_at')->nullable()->after('abandoned_at');
            $table->index(['status', 'abandoned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('acme_cart_carts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'abandoned_at']);
            $table->dropUnique(['recovery_token']);
            $table->dropColumn(['recovery_token', 'abandoned_at', 'reminded_at']);
        });
    }
};
