<?php

namespace Heart\Provider\Infrastructure\Factories;

use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(['twitch', 'discord']),
            'provider_id' => $this->faker->randomNumber(6),
            'email' => $this->faker->unique()->email,
        ];
    }
}
