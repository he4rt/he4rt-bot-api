<?php

namespace Heart\User\Infrastructure\Factories;

use Heart\Shared\Infrastructure\Factory;
use Heart\User\Infrastructure\Models\User;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'country' => $this->faker->countryCode(),
            'state' => $this->faker->randomElement(['SP', 'RJ', 'BH']),
            'city' => $this->faker->city(),
            'zip' => $this->faker->randomNumber(8)
        ];
    }
}
