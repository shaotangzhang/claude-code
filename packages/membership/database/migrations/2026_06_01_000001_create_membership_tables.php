<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_membership_tiers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedInteger('level')->default(0); // higher = more privileged
            $table->text('description')->nullable();
            $table->json('perks_json')->nullable();
            $table->timestamps();
            $table->index('level');
        });

        Schema::create('acme_membership_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tier_id')->constrained('acme_membership_tiers')->restrictOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('billing_period'); // once | monthly | quarterly | yearly
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('active')->default(true);
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->index(['tier_id', 'active']);
        });

        Schema::create('acme_membership_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('acme_auth_users')->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained('acme_membership_plans')->restrictOnDelete();
            $table->string('status'); // trialing | active | past_due | paused | canceled | expired
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('paused_until')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });

        Schema::create('acme_membership_subscription_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('subscription_id')->constrained('acme_membership_subscriptions')->cascadeOnDelete();
            $table->string('event_type'); // started | renewed | paused | resumed | canceled | expired | payment_due | payment_received
            $table->json('payload_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subscription_id', 'created_at'], 'mship_sub_evt_at_idx');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_membership_subscription_events');
        Schema::dropIfExists('acme_membership_subscriptions');
        Schema::dropIfExists('acme_membership_plans');
        Schema::dropIfExists('acme_membership_tiers');
    }
};
