<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpAndRankingInUserSeasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_seasons', function (Blueprint $table) {
            $table->integer('experience');
            $table->integer('ranking_position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_seasons', function (Blueprint $table) {
            $table->dropColumn(['experience', 'ranking_position']);
        });
    }
}
