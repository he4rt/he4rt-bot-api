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
    {if (!Schema::hasTable('messages')) {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('provider_id')->constrained('providers');
            $table->string('provider_message_id');
            $table->integer('season_id');
            $table->string('channel_id');
            $table->text('content');
            $table->integer('obtained_experience');
            $table->timestamp('sent_at');
            $table->timestamps();
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
        Schema::dropIfExists('messages');
    }
};
