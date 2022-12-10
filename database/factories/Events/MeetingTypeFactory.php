<?php

namespace Database\Factories\Events;

use App\Models\Events\MeetingType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingTypeFactory extends Factory
{
    protected $model = MeetingType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'week_day' => $this->faker->numberBetween(0, 6),
            'start_at' => $this->faker->numberBetween(0, 1439),
        ];
    }
}
