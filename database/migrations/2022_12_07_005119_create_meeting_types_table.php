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
        if (! Schema::hasTable('meeting_types')) {
            Schema::create('meeting_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('week_day')->comment('Week day of event');
                $table->integer('start_at')->comment('Number of minutes past midnight');
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
        Schema::dropIfExists('meeting_types');
    }
};
