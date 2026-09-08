<?php

declare(strict_types=1);

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
            // TODO(#540): trocar pelo comment gerado por TimelineReaction::stringifyCases() quando o enum existir.
            $table->string('reaction')->comment('like|love|laugh|celebrate|fire|sad');
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
