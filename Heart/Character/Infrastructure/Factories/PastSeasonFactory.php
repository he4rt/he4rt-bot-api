<?php

namespace Heart\Character\Infrastructure\Factories;

use Heart\Character\Infrastructure\Models\Character;
use Heart\Character\Infrastructure\Models\PastSeason;
use Illuminate\Database\Eloquent\Factories\Factory;

class PastSeasonFactory extends Factory
{
    protected $model = PastSeason::class;

    public function definition(): array
    {
        return [
            'season_id' => 2,
            'character_id' => Character::factory(),
            'ranking_position' => $this->faker->numberBetween(1, 1000),
            'experience' => $this->faker->numberBetween(1, 1000),
            'level' => 1,
            'messages_count' => $this->faker->numberBetween(1, 1000),
            'badges_count' => $this->faker->numberBetween(1, 1000),
            'meetings_count' => $this->faker->numberBetween(1, 1000),
        ];
    }
}
