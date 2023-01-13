<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRedeemCodeInBadgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->string('redeem_code')->after('description');
        });
    }

    public function down()
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn('redeem_code');
        });
    }
}
