<?php

declare(strict_types=1);

namespace He4rt\Contents\Database\Factories;

use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentEntry> */
final class ContentEntryFactory extends Factory
{
    protected $model = ContentEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'contentable_type' => 'content_article',
            'contentable_id' => Article::factory(),
            'author_id' => null,
            'author_handle' => fake()->userName(),
            'provider' => ContentProvider::DevTo,
            'external_id' => (string) fake()->unique()->numberBetween(1, 999_999),
            'title' => fake()->sentence(),
            'url' => fake()->url(),
            'thumbnail_url' => null,
            'tags' => [],
            'published_at' => fake()->dateTimeBetween('-1 month'),
            'reactions_count' => null,
            'comments_count' => null,
            'saves_count' => null,
            'metrics_synced_at' => null,
        ];
    }

    public function authoredBy(User $user): static
    {
        return $this->state([
            'author_id' => $user->id,
            'author_handle' => $user->name ?? fake()->userName(),
        ]);
    }

    public function withMetrics(int $reactions, int $comments, ?int $saves = null): static
    {
        return $this->state([
            'reactions_count' => $reactions,
            'comments_count' => $comments,
            'saves_count' => $saves,
            'metrics_synced_at' => now(),
        ]);
    }
}
