<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_auth_users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('two_factor_secret')->nullable();
            $table->json('two_factor_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('acme_auth_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('acme_auth_users')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('acme_auth_login_log', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('email');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('result'); // success | failed | locked
            $table->timestamp('attempted_at');
            $table->index(['email', 'attempted_at']);
        });

        Schema::create('acme_auth_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('email');
            $table->string('token', 80)->unique();
            $table->foreignUlid('inviter_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_auth_invitations');
        Schema::dropIfExists('acme_auth_login_log');
        Schema::dropIfExists('acme_auth_sessions');
        Schema::dropIfExists('acme_auth_users');
    }
};
