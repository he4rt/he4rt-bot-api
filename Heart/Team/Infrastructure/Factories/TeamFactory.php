<?php

namespace Heart\Team\Infrastructure\Factories;

use Heart\Team\Infrastructure\Models\Team;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'logo_url' => $this->faker->imageUrl(),
            'slug' => $this->faker->slug(),
            'leader_id' => User::factory()
        ];
    }
}
