<?php

declare(strict_types=1);

use He4rt\Activity\Reaction\Enums\TimelineReaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_user_reactions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('timeline_id')->constrained('activity_timeline')->cascadeOnDelete();
            $table->string('reaction')->comment(TimelineReaction::stringifyCases());
            $table->timestampsTz();

            $table->unique(['user_id', 'timeline_id'], 'activity_user_reactions_user_timeline_unique');
            $table->index(['timeline_id', 'reaction'], 'activity_user_reactions_timeline_reaction_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_user_reactions');
    }
};
