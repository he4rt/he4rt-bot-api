<?php

namespace Database\Factories\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'discord_id' => $this->faker->unique()->randomNumber(8),
            'twitch_id' => $this->faker->unique()->randomNumber(8),
            'email' => $this->faker->unique()->safeEmail,
            'level' => $this->faker->numberBetween(1,30),
            'current_exp' => $this->faker->numberBetween(100,5000),
            'money' => $this->faker->numberBetween(1,10000) ,
            'nickname' => $this->faker->userName
        ];
    }
}
