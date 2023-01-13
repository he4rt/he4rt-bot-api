<?php

namespace Database\Seeders;

use App\Models\Gamefication\Season;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (config('he4rt.seasons') as $season) {
            Season::query()->create($season);
        }
    }
}
