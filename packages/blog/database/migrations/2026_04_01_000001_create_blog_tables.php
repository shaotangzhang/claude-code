<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_blog_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')->nullable()->constrained('acme_blog_categories')->nullOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('locale')->default('en');
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('acme_blog_tags', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug');
            $table->string('name');
            $table->string('locale')->default('en');
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('acme_blog_articles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('author_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('acme_blog_categories')->nullOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('locale')->default('en');
            $table->string('status')->default('draft'); // draft|scheduled|published|archived
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['locale', 'slug']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('acme_blog_article_tag', function (Blueprint $table): void {
            $table->foreignUlid('article_id')->constrained('acme_blog_articles')->cascadeOnDelete();
            $table->foreignUlid('tag_id')->constrained('acme_blog_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('acme_blog_comments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('article_id')->constrained('acme_blog_articles')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('acme_blog_comments')->cascadeOnDelete();
            $table->foreignUlid('author_user_id')->nullable()->constrained('acme_auth_users')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->string('status')->default('pending'); // pending|approved|spam|trash
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamps();
            $table->index(['article_id', 'status', 'created_at']);
        });

        Schema::create('acme_blog_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('email');
            $table->string('token', 80)->unique();
            $table->string('locale', 12)->default('en');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->unique(['email', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_blog_subscriptions');
        Schema::dropIfExists('acme_blog_comments');
        Schema::dropIfExists('acme_blog_article_tag');
        Schema::dropIfExists('acme_blog_articles');
        Schema::dropIfExists('acme_blog_tags');
        Schema::dropIfExists('acme_blog_categories');
    }
};
