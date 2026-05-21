<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_notifications_preferences', function (Blueprint $table): void {
            $table->foreignUlid('user_id')->constrained('acme_auth_users')->cascadeOnDelete();
            $table->string('event_type');     // 'order.paid' etc.
            $table->string('channel');         // 'mail' | 'log' | 'sms' | ...
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->primary(['user_id', 'event_type', 'channel'], 'notify_pref_pk');
        });

        Schema::create('acme_notifications_log', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('event_type');
            $table->string('channel');
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('recipient')->nullable();   // email / phone / endpoint
            $table->json('payload_json')->nullable();
            $table->string('status'); // sent | failed | skipped
            $table->string('failure_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_notifications_log');
        Schema::dropIfExists('acme_notifications_preferences');
    }
};
