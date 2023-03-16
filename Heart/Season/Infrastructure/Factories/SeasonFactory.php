<?php

namespace Heart\Season\Infrastructure\Factories;

use Heart\Season\Infrastructure\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'messages_count' => $this->faker->randomNumber(2),
            'participants_count' => $this->faker->randomNumber(2),
            'meeting_count' => $this->faker->randomNumber(2),
            'badges_count' => $this->faker->randomNumber(2),
            'started_at' => $this->faker->dateTimeBetween('-1 hour'),
            'ended_at' => $this->faker->dateTimeBetween('+1 hour', '+2 hour'),
        ];
    }
}
