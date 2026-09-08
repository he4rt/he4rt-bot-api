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
        $caption = mb_trim($data->caption ?? '');

        $media
            ->setCustomProperty(Photo::CAPTION, $caption === '' ? null : $caption)
            ->setCustomProperty(Photo::HIGHLIGHT, $data->highlight);

        $media->save();

        return $media;
    }
}
