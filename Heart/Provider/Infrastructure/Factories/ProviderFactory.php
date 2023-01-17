<?php

namespace Heart\Provider\Infrastructure\Factories;

use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'subscriber_id' => Subscriber::factory(),
            'provider' => $this->faker->randomElement(['twitch', 'google', 'apoiase', 'github']),
            'provider_id' => $this->faker->uuid,
            'email' => $this->faker->unique()->email,
        ];
    }
}
