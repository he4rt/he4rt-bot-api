<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->text('description')->nullable();
            $table->integer('reading_time_minutes')->nullable();
            $table->text('canonical_url')->nullable();
            $table->text('body_markdown')->nullable();
            $table->text('body_html')->nullable();
            $table->timestampTz('source_edited_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_articles');
    }
};
