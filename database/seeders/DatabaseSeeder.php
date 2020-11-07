<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('APP_ENV') !== "production") {
            $this->call(UserSeeder::class);
        }

        $this->call(ExpTableSeeder::class);
        $this->call(SeasonSeeder::class);
    }
}
