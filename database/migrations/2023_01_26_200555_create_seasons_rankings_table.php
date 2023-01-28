<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seasons_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('season_id');
            $table->foreignUuid('character_id')->constrained('characters');
            $table->integer('ranking_position');
            $table->integer('experience');
            $table->integer('messages_count');
            $table->integer('badges_count');
            $table->integer('meetings_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seasons_rankings');
    }
};
