<?php

declare(strict_types=1);

namespace He4rt\Live\Database\Factories;

use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Models\Live;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Live> */
final class LiveFactory extends Factory
{
    protected $model = Live::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => LiveStatus::Created,
            'stream_key' => Str::random(40),
            'peak_viewers' => 0,
        ];
    }

    public function onAir(): self
    {
        return $this->state(['status' => LiveStatus::OnAir, 'started_at' => now()]);
    }

    public function ended(): self
    {
        return $this->state(['status' => LiveStatus::Ended, 'started_at' => now()->subHour(), 'ended_at' => now()]);
    }
}
