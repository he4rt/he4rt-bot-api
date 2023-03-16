<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (! Schema::hasTable('voice_messages')) {
            Schema::create('voice_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('provider_id')->constrained('providers');
                $table->integer('season_id');
                $table->string('channel_name');
                $table->string('state');
                $table->integer('obtained_experience');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_messages');
    }
};
