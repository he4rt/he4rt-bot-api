<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feedback_id')->references('id')->on('feedback');
            $table->foreignId('staff_id')->references('id')->on('users');
            $table->string('decline_message');
            $table->timestamp('approved_at');
            $table->timestamp('declined_at');

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
        Schema::dropIfExists('feedback_reviews');
    }
}
