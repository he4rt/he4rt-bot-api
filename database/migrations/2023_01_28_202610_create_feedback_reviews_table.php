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
        Schema::create('feedback_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('feedback_id')->constrained('feedbacks');
            $table->foreignUuid('staff_id')->constrained('users');
            $table->string('status');
            $table->text('reason')->nullable();
            $table->timestamp('received_at');
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
};
