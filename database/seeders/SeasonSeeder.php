<?php

namespace Database\Seeders;

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

        DB::table('seasons')->insert([
            'name' => 'fodase',
            'start' => '2019-01-01',
            'end' => '2020-12-30',
            'status' => true
        ]);

        DB::table('seasons')->insert([
            'name' => 'season 2 do granchase',
            'start' => '2021-01-01',
            'end' => '2021-12-30',
            'status' => false
        ]);
    }
}
