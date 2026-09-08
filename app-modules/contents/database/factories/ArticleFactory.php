<?php

declare(strict_types=1);

namespace He4rt\Contents\Database\Factories;

use He4rt\Contents\Articles\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'description' => null,
            'reading_time_minutes' => null,
            'canonical_url' => null,
            'body_markdown' => null,
            'body_html' => null,
            'source_edited_at' => null,
        ];
    }

    public function hydrated(): static
    {
        return $this->state([
            'body_markdown' => fake()->paragraphs(3, asText: true),
            'body_html' => '<p>'.fake()->paragraphs(3, asText: true).'</p>',
            'source_edited_at' => now(),
        ]);
    }
}
