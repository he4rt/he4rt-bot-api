<?php

namespace Heart\Character\Infrastructure\Factories;

use Heart\Character\Infrastructure\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Illuminate\Database\Eloquent\Model>
 */
class CharacterFactory extends Factory
{
    protected $model = Character::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => '1',
            'reputation' => $this->faker->numberBetween(1, 10),
            'experience' => $this->faker->numberBetween(1, 5000),
            'daily_bonus_claimed_at' => $this->faker->date(),
        ];
    }
}
