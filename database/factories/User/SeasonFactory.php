<?php

namespace Database\Factories\User;

use App\Models\User\Season;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'season_id' => 1,
            'level' => $this->faker->randomNumber(2),
            'messages_count' => $this->faker->randomNumber(5),
            'badges_count' => $this->faker->randomNumber(5),
            'meetings_count' => $this->faker->randomNumber(5),
            'experience' => $this->faker->randomNumber(5),
            'ranking_position' => $this->faker->randomNumber(5),
        ];
    }
}
