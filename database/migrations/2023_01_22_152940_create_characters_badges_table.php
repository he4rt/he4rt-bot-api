<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('characters_badges')) {
            Schema::create('characters_badges', function (Blueprint $table) {
                $table->foreignUuid('character_id');
                $table->foreignId('badge_id');
                $table->timestamp('claimed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('characters_badges');
    }
};
