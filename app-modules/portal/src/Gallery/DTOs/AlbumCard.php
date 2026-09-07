<?php

declare(strict_types=1);

namespace He4rt\Portal\Gallery\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class AlbumCard
{
    /**
     * @param  list<PhotoView>  $highlights
     */
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $location,
        public CarbonImmutable $happenedAt,
        public int $photoCount,
        public array $highlights,
    ) {}

    public static function fromAlbum(Album $album): self
    {
        return new self(
            slug: $album->slug,
            title: $album->title,
            location: $album->location,
            happenedAt: $album->happened_at->toImmutable(),
            photoCount: $album->photos_count ?? $album->getMedia(Album::PHOTOS)->count(),
            highlights: array_values(
                $album->highlights()
                    ->take(Album::CARD_HIGHLIGHTS)
                    ->map(fn (Media $media): PhotoView => PhotoView::fromMedia($media))
                    ->all(),
            ),
        );
    }

    public function remaining(): int
    {
        return max(0, $this->photoCount - count($this->highlights));
    }

    public function happenedLabel(): string
    {
        return Str::ucfirst($this->happenedAt->translatedFormat('F \d\e Y'));
    }

    public function photoCountLabel(): string
    {
        return sprintf('%d %s', $this->photoCount, Str::plural('foto', $this->photoCount));
    }
}
