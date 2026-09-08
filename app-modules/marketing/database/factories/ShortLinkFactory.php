<?php

declare(strict_types=1);

namespace He4rt\Marketing\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShortLink> */
final class ShortLinkFactory extends Factory
{
    protected $model = ShortLink::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $baseSlug = SlugGenerator::base(fake()->unique()->slug(nbWords: 2));

        return [
            'slug' => $baseSlug.'-'.SlugGenerator::suffix(),
            'base_slug' => $baseSlug,
            'destination_url' => fake()->url(),
            'utm' => null,
            'tags' => [],
            'active' => true,
            'expires_at' => null,
            'clicks_count' => 0,
            'human_clicks_count' => 0,
            'created_by' => User::factory(),
        ];
    }

    /** Past its expiry date, but still `active`. */
    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    /** Disabled by hand, with an expiry still in the future. */
    public function disabled(): static
    {
        return $this->state([
            'active' => false,
            'expires_at' => now()->addMonth(),
        ]);
    }

    /** @param array<string, string> $utm */
    public function withUtm(array $utm = ['utm_source' => 'discord', 'utm_medium' => 'post']): static
    {
        return $this->state(['utm' => $utm]);
    }

    /** @param list<string> $tags */
    public function withTags(array $tags = ['comunidade']): static
    {
        return $this->state(['tags' => $tags]);
    }

    public function withClicks(int $total = 100, int $human = 90): static
    {
        return $this->state([
            'clicks_count' => $total,
            'human_clicks_count' => $human,
        ]);
    }
}
