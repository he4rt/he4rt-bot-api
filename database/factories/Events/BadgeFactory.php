<?php

namespace Database\Factories\Events;

use App\Models\Events\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->words(),
            'image_url' => $this->faker->imageUrl(),
            'redeem_code' => $this->faker->slug(2),
            'active' => false
        ];
    }
}
