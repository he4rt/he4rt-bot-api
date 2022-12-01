<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInSeasonsTable extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn(['status', 'start', 'end']);

            $table->text('description')
                ->nullable()
                ->after('name');
            $table->timestamp('start_at')
                ->after('description')
                ->useCurrent();
            $table->timestamp('ends_at')
                ->after('start_at')
                ->useCurrent();

            $table->bigInteger('participants_count')
                ->nullable('ends_at')
                ->default(0);
            $table->bigInteger('messages_count')
                ->nullable('participants_count')
                ->default(0);
        });
        // TODO: create a daily job to update information about the current season.
        // TODO: logging amount of participants during the season
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'start_at',
                'ends_at',
                'participants_count',
                'messages_count',
            ]);

            $table->boolean('active')->after('name')->default(false);
            $table->date('start')->after('name')->useCurrent();
            $table->date('end')->after('name')->useCurrent();
        });
    }
}
