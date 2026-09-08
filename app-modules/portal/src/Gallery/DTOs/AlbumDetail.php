<?php

declare(strict_types=1);

namespace He4rt\Portal\Gallery\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class AlbumDetail
{
    /**
     * @param  list<PhotoView>  $photos
     */
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $location,
        public CarbonImmutable $happenedAt,
        public ?string $description,
        public ?string $eventTitle,
        public array $photos,
        public ?PhotoView $cover,
    ) {}

    public static function fromAlbum(Album $album): self
    {
        $cover = $album->cover();

        return new self(
            slug: $album->slug,
            title: $album->title,
            location: $album->location,
            happenedAt: $album->happened_at->toImmutable(),
            description: $album->description,
            eventTitle: $album->event?->title,
            photos: array_values(
                $album->getMedia(Album::PHOTOS)
                    ->map(fn (Media $media): PhotoView => PhotoView::fromMedia($media))
                    ->all(),
            ),
            cover: $cover instanceof Media ? PhotoView::fromMedia($cover) : null,
        );
    }

    public function photoCount(): int
    {
        return count($this->photos);
    }

    public function happenedLabel(): string
    {
        return Str::ucfirst($this->happenedAt->translatedFormat('F \d\e Y'));
    }

    public function photoCountLabel(): string
    {
        return sprintf('%d %s', $this->photoCount(), Str::plural('foto', $this->photoCount()));
    }

    /**
     * Payload mínimo do lightbox: a versão ampliada e a legenda de cada foto.
     *
     * @return list<array{l: string, c: string|null}>
     */
    public function lightboxPayload(): array
    {
        return array_map(
            static fn (PhotoView $photo): array => ['l' => $photo->largeUrl, 'c' => $photo->caption],
            $this->photos,
        );
    }
}
