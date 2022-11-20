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

    public function definition(): array
    {
        return [
            'discord_id' => $this->faker->unique()->randomNumber(8),
            'twitch_id' => $this->faker->unique()->randomNumber(8),
            'email' => $this->faker->unique()->safeEmail,
            'level' => 1,
            'current_exp' => $this->faker->numberBetween(100,5000),
            'money' => $this->faker->numberBetween(1,10000) ,
            'nickname' => $this->faker->userName,
            'git' => $this->faker->url,
            'name' => $this->faker->name,
            'about' => $this->faker->sentence(),
            'daily' => null,
            'reputation' => 0,
        ];
    }
}
