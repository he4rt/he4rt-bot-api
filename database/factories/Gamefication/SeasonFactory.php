<?php

namespace Database\Factories\Gamefication;

use App\Models\Gamefication\Season;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'start' => Carbon::now(),
            'end' => Carbon::now()->addMonth(),
            'status' => true
        ];
    }
}
