<?php

namespace Heart\User\Infrastructure\Factories;

use Heart\User\Infrastructure\Models\Information;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InformationFactory extends Factory
{
    protected $model = Information::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'nickname' => $this->faker->userName(),
            'linkedin_url' => $this->faker->url(),
            'github_url' => $this->faker->url(),
            'birthdate' => $this->faker->date(),
            'about' => $this->faker->text(),
        ];
    }
}
