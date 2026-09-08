<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_albums', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('slug', 120);
            $table->string('title', 200);
            $table->string('location')->nullable();
            $table->date('happened_at');
            $table->text('description')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();

            $table->unique('slug', 'idx_events_albums_slug');
            $table->index(['published_at', 'happened_at'], 'idx_events_albums_public_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_albums');
    }
};
