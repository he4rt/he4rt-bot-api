<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_viewer_samples', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('live_id')->constrained('lives')->cascadeOnDelete();
            $table->integer('viewers');
            $table->timestampTz('sampled_at');
            $table->timestampsTz();
            $table->index(['live_id', 'sampled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_viewer_samples');
    }
};
