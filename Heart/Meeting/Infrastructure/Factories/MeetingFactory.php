<?php

namespace Heart\Meeting\Infrastructure\Factories;

use Heart\Meeting\Infrastructure\Models\Meeting;
use Heart\Meeting\Infrastructure\Models\MeetingType;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'meeting_type_id' => MeetingType::factory(),
            'admin_id' => User::factory(),
            'content' => 'Fake content',
            'starts_at' => $this->faker->dateTimeBetween('-1 hour'),
            'ends_at' => $this->faker->dateTimeBetween('+1 hour', '+2 hour'),
        ];
    }

    public function unfinished(): self
    {
        return $this->state(fn() => ['ends_at' => null, 'content' => null]);
    }
}
