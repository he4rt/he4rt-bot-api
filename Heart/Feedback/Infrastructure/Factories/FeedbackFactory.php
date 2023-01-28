<?php

namespace Heart\Feedback\Infrastructure\Factories;

use Heart\Feedback\Infrastructure\Models\Feedback;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{

    protected $model = Feedback::class;
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'sender_id' => User::factory(),
            'target_id' => User::factory(),
            'type' => $this->faker->randomElement(['compliment', 'improvement']),
            'message' => $this->faker->sentence()
        ];
    }
}
