<?php

namespace Heart\Message\Infrastructure\Factories;

use Heart\Message\Infrastructure\Models\Message;
use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'provider_id' => Provider::factory(),
            'provider_message_id' => $this->faker->randomNumber(4),
            'season_id' => 2,
            'channel_id' => $this->faker->randomNumber(4),
            'content' => $this->faker->sentence(),
            'sent_at' => now(),
            'obtained_experience' => $this->faker->randomNumber(2),
        ];
    }
}
