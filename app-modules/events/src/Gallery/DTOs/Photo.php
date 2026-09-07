<?php

declare(strict_types=1);

namespace He4rt\Events\Gallery\DTOs;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Leitura tipada de uma foto de álbum. Legenda e destaque moram nas custom
 * properties da mídia, e as chaves só existem aqui.
 */
final readonly class Photo
{
    public const string CAPTION = 'caption';

    public const string HIGHLIGHT = 'highlight';

    public function __construct(
        public int $id,
        public string $uuid,
        public ?string $caption,
        public bool $highlight,
        public int $position,
    ) {}

    public static function fromMedia(Media $media): self
    {
        $caption = $media->getCustomProperty(self::CAPTION);

        return new self(
            id: (int) $media->getKey(),
            uuid: (string) $media->uuid,
            caption: is_string($caption) && $caption !== '' ? $caption : null,
            highlight: (bool) $media->getCustomProperty(self::HIGHLIGHT, default: false),
            position: (int) ($media->order_column ?? 0),
        );
    }
}
