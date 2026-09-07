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
        Schema::create('lives', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('created');
            $table->text('stream_key');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->integer('peak_viewers')->default(0);
            $table->timestampsTz();
        });

        DB::statement("CREATE UNIQUE INDEX lives_single_current_unique ON lives ((status <> 'ended')) WHERE status <> 'ended'");
    }

    public function down(): void
    {
        Schema::dropIfExists('lives');
    }
};
