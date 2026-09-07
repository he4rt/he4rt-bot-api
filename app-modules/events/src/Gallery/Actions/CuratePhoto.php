<?php

declare(strict_types=1);

namespace He4rt\Events\Gallery\Actions;

use He4rt\Events\Gallery\DTOs\CuratePhotoData;
use He4rt\Events\Gallery\DTOs\Photo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class CuratePhoto
{
    public function handle(Media $media, CuratePhotoData $data): Media
    {
        $caption = $data->caption !== null ? mb_trim($data->caption) : null;

        $media
            ->setCustomProperty(Photo::CAPTION, $caption !== '' ? $caption : null)
            ->setCustomProperty(Photo::HIGHLIGHT, $data->highlight);

        $media->save();

        return $media;
    }
}
