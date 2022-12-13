<?php

use App\Models\Gamefication\Season;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnsInSeasonsTable extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('seasons')->truncate();
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn(['status', 'start', 'end']);

            $table->text('description')
                ->nullable()
                ->after('name');
            $table->timestamp('starts_at')
                ->after('description')
                ->useCurrent();
            $table->timestamp('ends_at')
                ->after('starts_at')
                ->useCurrent();

            $table->bigInteger('participants_count')
                ->nullable('ends_at')
                ->default(0);
            $table->bigInteger('messages_count')
                ->nullable('participants_count')
                ->default(0);
            $table->bigInteger('badges_count')
                ->nullable('messages_count')
                ->default(0);
            $table->bigInteger('meetings_count')
                ->nullable('badges_count')
                ->default(0);
        });

        foreach (config('he4rt.seasons') as $season) {
            Season::query()->create($season);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'starts_at',
                'ends_at',
                'participants_count',
                'messages_count',
            ]);

            $table->boolean('active')->after('name')->default(false);
            $table->date('end')->after('name')->nullable();
            $table->date('start')->after('name')->nullable();
        });
    }
}
