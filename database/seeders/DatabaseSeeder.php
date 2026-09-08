<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use He4rt\Events\Database\Seeders\EventsSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BaseSeeder::class,
            EventsSeeder::class,
            RetrospectiveDemoSeeder::class,
        ]);
    }
}
