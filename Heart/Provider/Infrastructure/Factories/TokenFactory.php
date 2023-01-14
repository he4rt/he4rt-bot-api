<?php

namespace Heart\Provider\Infrastructure\Factories;

use Heart\Provider\Infrastructure\Models\Provider;
use Heart\Provider\Infrastructure\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenFactory extends Factory
{
    protected $model = Token::class;
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'provider_id' => Provider::factory(),
            'access_token' => $this->faker->uuid(),
            'refresh_token' => $this->faker->uuid(),
            'expires_in' => $this->faker->randomNumber(4)
        ];
    }
}
