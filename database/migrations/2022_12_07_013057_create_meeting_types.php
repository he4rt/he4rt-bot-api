<?php

use App\Models\Events\MeetingType;
use Illuminate\Database\Migrations\Migration;

class CreateMeetingTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        MeetingType::create([
            'name' => 'Reunião Semanal',
            'week_day' => 1,
            'start_at' => 1320,
        ]);

        MeetingType::create([
            'name' => 'Reunião das Primas',
            'week_day' => 2,
            'start_at' => 1200,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        MeetingType::whereIn('name', ['Reunião Semanal', 'Reunião das Primas'])->forceDelete();
    }
}
