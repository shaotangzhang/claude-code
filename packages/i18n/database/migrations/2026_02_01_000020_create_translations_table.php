<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_i18n_translations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('translatable_type');
            $table->ulid('translatable_id');
            $table->string('field');
            $table->string('locale', 12);
            $table->text('value');
            $table->timestamps();
            $table->unique(['translatable_type', 'translatable_id', 'field', 'locale'], 'i18n_uniq');
            $table->index(['translatable_type', 'translatable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_i18n_translations');
    }
};
