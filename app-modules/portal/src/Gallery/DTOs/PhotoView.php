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
            thumbUrl: PhotoConversion::Thumb->urlFor($media),
            largeUrl: PhotoConversion::Large->urlFor($media),
            caption: $photo->caption,
            highlight: $photo->highlight,
        );
    }
}
