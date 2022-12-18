<?php

namespace Database\Factories\Gamefication;

use App\Models\Gamefication\Season;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text(),
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addYear(),
            'messages_count' => 0,
            'participants_count' => 0
        ];
    }

    public function activeSeason()
    {
        return $this->state([
            'starts_at' => Carbon::parse('2019-01-01'),
            'ends_at' => Carbon::now()->addYear()
        ]);
    }
}
