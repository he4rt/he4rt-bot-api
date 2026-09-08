<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('contentable');
            $table->foreignUuid('author_id')->nullable();
            $table->string('author_handle');
            $table->string('provider');
            $table->string('external_id');
            $table->string('title');
            $table->text('url');
            $table->text('thumbnail_url')->nullable();
            $table->jsonb('tags')->default('[]');
            $table->timestampTz('published_at');
            $table->integer('reactions_count')->nullable();
            $table->integer('comments_count')->nullable();
            $table->integer('saves_count')->nullable();
            $table->timestampTz('metrics_synced_at')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'external_id']);
            $table->index('published_at');
        });

        DB::statement('CREATE INDEX content_entries_orphan_reconciliation_index ON content_entries (provider, author_handle) WHERE author_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entries');
    }
};
