<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsInUserSeasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_seasons', function (Blueprint $table) {
            $table->bigInteger('meetings_count');
            $table->bigInteger('badges_count');
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
            $table->dropColumn(['meetings_count', 'badges_count']);
        });
    }
}
