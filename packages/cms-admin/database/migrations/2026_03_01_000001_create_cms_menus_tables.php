<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_cms_menus', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('locale')->default('en');
            $table->timestamps();
        });

        Schema::create('acme_cms_menu_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('menu_id')->constrained('acme_cms_menus')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('acme_cms_menu_items')->cascadeOnDelete();
            $table->string('label');
            $table->string('route')->nullable();   // named route
            $table->string('url')->nullable();     // raw URL
            $table->unsignedInteger('position')->default(0);
            $table->json('attrs_json')->nullable(); // target, rel, icon...
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_cms_menu_items');
        Schema::dropIfExists('acme_cms_menus');
    }
};
