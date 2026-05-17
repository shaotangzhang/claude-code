<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_rbac_capabilities', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('label');
            $table->string('group')->nullable();
            $table->timestamps();
            $table->index('group');
        });

        Schema::create('acme_rbac_roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('acme_rbac_role_capability', function (Blueprint $table): void {
            $table->foreignUlid('role_id')->constrained('acme_rbac_roles')->cascadeOnDelete();
            $table->string('capability_key');
            $table->primary(['role_id', 'capability_key']);
            $table->foreign('capability_key')->references('key')->on('acme_rbac_capabilities')->cascadeOnDelete();
        });

        Schema::create('acme_rbac_role_user', function (Blueprint $table): void {
            $table->foreignUlid('role_id')->constrained('acme_rbac_roles')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('acme_auth_users')->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_rbac_role_user');
        Schema::dropIfExists('acme_rbac_role_capability');
        Schema::dropIfExists('acme_rbac_roles');
        Schema::dropIfExists('acme_rbac_capabilities');
    }
};
