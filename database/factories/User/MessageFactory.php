<?php

namespace Database\Factories\User;

use App\Models\Gamefication\Season;
use App\Models\User\Message;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'season_id' => Season::factory(),
            'message_content' => $this->faker->text(),
            'obtained_experience' => 1
        ];
    }

    public function willLevelUp()
    {
        return $this->state([
            'user_id' => User::factory(),
            'season_id' => config('he4rt.season.id'),
            'message_content' => $this->faker->text(),
            'obtained_experience' => 999999
        ]);
    }
}
