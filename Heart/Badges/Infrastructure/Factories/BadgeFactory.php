<?php

namespace Heart\Badges\Infrastructure\Factories;

use Heart\Badges\Infrastructure\Model\Badge;
use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'provider' => Provider::factory(),
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'image_url' => $this->faker->imageUrl(),
            'redeem_code' => $this->faker->slug(2),
            'active' => true
        ];
    }
}
