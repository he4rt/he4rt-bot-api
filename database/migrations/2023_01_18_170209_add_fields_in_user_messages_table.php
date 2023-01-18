<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsInUserMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_messages', function (Blueprint $table) {
            $table->text('message')->change();
            $table->renameColumn('message', 'message_content');
            $table->string('channel_id')->nullable()->after('user_id');
            $table->string('message_id')->nullable()->after('channel_id');
            $table->timestamp('sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_messages', function (Blueprint $table) {
            $table->renameColumn('message_content', 'message');
            $table->dropColumn(['channel_id', 'message_id', 'sent_at']);
        });
    }
}
