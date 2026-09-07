<?php

declare(strict_types=1);

namespace He4rt\Portal\Gallery\DTOs;

use He4rt\Events\Gallery\DTOs\Photo;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class PhotoView
{
    public function __construct(
        public string $thumbUrl,
        public string $largeUrl,
        public ?string $caption,
        public bool $highlight,
    ) {}

    public static function fromMedia(Media $media): self
    {
        $photo = Photo::fromMedia($media);

        return new self(
            thumbUrl: self::url($media, PhotoConversion::Thumb),
            largeUrl: self::url($media, PhotoConversion::Large),
            caption: $photo->caption,
            highlight: $photo->highlight,
        );
    }

    /**
     * A conversão roda em fila. Até ela existir, o portal serve o original
     * em vez de um link quebrado.
     */
    private static function url(Media $media, PhotoConversion $conversion): string
    {
        return $media->hasGeneratedConversion($conversion->value)
            ? $media->getUrl($conversion->value)
            : $media->getUrl();
    }
}
