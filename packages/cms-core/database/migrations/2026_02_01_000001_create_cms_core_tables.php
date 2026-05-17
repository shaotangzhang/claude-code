<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_cms_themes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('version');
            $table->string('screenshot')->nullable();
            $table->json('manifest_json')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->index('active');
        });

        Schema::create('acme_cms_layouts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('theme_id')->nullable()->constrained('acme_cms_themes')->nullOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('template');
            $table->json('slots_json')->nullable();
            $table->timestamps();
            $table->unique(['theme_id', 'key']);
        });

        Schema::create('acme_cms_pages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('layout_id')->constrained('acme_cms_layouts')->restrictOnDelete();
            $table->ulid('current_version_id')->nullable();
            $table->string('slug');
            $table->string('locale')->default('en');
            $table->string('title');
            $table->string('status')->default('draft'); // draft | scheduled | published | archived
            $table->timestamp('publish_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug']);
            $table->index(['status', 'publish_at']);
        });

        Schema::create('acme_cms_page_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('acme_cms_pages')->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->json('snapshot_json');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['page_id', 'created_at']);
        });

        Schema::create('acme_cms_blocks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_version_id')->constrained('acme_cms_page_versions')->cascadeOnDelete();
            $table->string('slot_key');
            $table->unsignedInteger('position')->default(0);
            $table->string('block_type');
            $table->json('data_json');
            $table->string('locale')->nullable();
            $table->index(['page_version_id', 'slot_key', 'position']);
        });

        Schema::create('acme_cms_widgets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('type');
            $table->json('data_json');
            $table->json('scopes_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_cms_widgets');
        Schema::dropIfExists('acme_cms_blocks');
        Schema::dropIfExists('acme_cms_page_versions');
        Schema::dropIfExists('acme_cms_pages');
        Schema::dropIfExists('acme_cms_layouts');
        Schema::dropIfExists('acme_cms_themes');
    }
};
