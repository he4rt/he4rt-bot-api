<?php

namespace Heart\User\Infrastructure\Factories;

use Heart\User\Infrastructure\Models\Address;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'country' => $this->faker->countryCode(),
            'state' => $this->faker->randomElement(['SP', 'RJ', 'BH']),
            'city' => $this->faker->city(),
            'zip_code' => $this->faker->randomNumber(8)
        ];
    }
}
