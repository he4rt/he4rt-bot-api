<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Event\Models\Event;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** @extends Factory<Album> */
final class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        $title = mb_rtrim(fake()->unique()->sentence(3), '.');

        return [
            'event_id' => null,
            'slug' => Str::slug($title),
            'title' => Str::title($title),
            'location' => fake()->optional()->city(),
            'happened_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'description' => fake()->optional()->sentence(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'published_at' => now()->subMinute(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'published_at' => now()->addDay(),
        ]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn (): array => [
            'event_id' => $event->id,
        ]);
    }

    /**
     * Exige Storage::fake('public') no teste: as fotos vão para o disco da
     * collection.
     */
    public function withPhotos(int $count = 3): static
    {
        return $this->afterCreating(static function (Album $album) use ($count): void {
            for ($index = 1; $index <= $count; $index++) {
                $album
                    ->addMedia(UploadedFile::fake()->image("foto-{$index}.jpg", 1_600, 1_200))
                    ->toMediaCollection(Album::PHOTOS);
            }
        });
    }
}
