<?php

namespace Database\Factories\Feedback;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    public function definition()
    {
        return [
            'sender_id' => User::factory()->create()->getKey(),
            'target_id' => User::factory()->create()->getKey(),
            'message'   => $this->faker->text,
            'type'      => 'bad',
        ];
    }
}
