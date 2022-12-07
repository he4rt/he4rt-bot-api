<?php

namespace Database\Factories\Events;

use App\Models\Events\Meeting;
use App\Models\Events\MeetingType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'meeting_type_id' => MeetingType::factory(),
            'user_created_id' => User::factory(),
            'starts_at' => $this->faker->dateTimeBetween('-1 hour'),
            'ends_at' => $this->faker->dateTimeBetween('+1 hour', '+2 hour'),
        ];
    }

    public function unfinished(): self
    {
        return $this->state(fn() => ['ends_at' => null]);
    }
}
