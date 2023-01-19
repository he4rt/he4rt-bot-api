<?php

namespace Heart\User\Infrastructure\Factories;

use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'username' => $this->faker->userName(),
            'is_donator' => false
        ];
    }
}
