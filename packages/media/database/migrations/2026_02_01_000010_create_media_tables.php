<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_media_files', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('disk');
            $table->string('path');
            $table->string('mime', 191);
            $table->unsignedBigInteger('size');
            $table->string('alt')->nullable();
            $table->json('meta_json')->nullable();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->timestamps();
            $table->index('mime');
        });

        Schema::create('acme_media_attachments', function (Blueprint $table): void {
            $table->foreignUlid('file_id')->constrained('acme_media_files')->cascadeOnDelete();
            $table->string('attachable_type');
            $table->ulid('attachable_id');
            $table->string('role')->default('default');
            $table->unsignedInteger('position')->default(0);
            $table->primary(['file_id', 'attachable_type', 'attachable_id', 'role'], 'media_att_pk');
            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_media_attachments');
        Schema::dropIfExists('acme_media_files');
    }
};
