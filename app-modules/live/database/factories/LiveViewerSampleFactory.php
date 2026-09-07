<?php

declare(strict_types=1);

namespace He4rt\Live\Database\Factories;

use He4rt\Live\Models\Live;
use He4rt\Live\Models\LiveViewerSample;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LiveViewerSample> */
final class LiveViewerSampleFactory extends Factory
{
    protected $model = LiveViewerSample::class;

    public function definition(): array
    {
        return [
            'live_id' => Live::factory(),
            'viewers' => fake()->numberBetween(0, 100),
            'sampled_at' => now(),
        ];
    }
}
