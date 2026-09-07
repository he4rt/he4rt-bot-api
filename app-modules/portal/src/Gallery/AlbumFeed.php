<?php

declare(strict_types=1);

namespace He4rt\Portal\Gallery;

use He4rt\Events\Gallery\Models\Album;
use He4rt\Portal\Gallery\DTOs\AlbumCard;
use He4rt\Portal\Gallery\DTOs\AlbumDetail;

/**
 * Read model da galeria pública.
 *
 * Só álbuns publicados e com pelo menos uma foto chegam aqui. A relação
 * `media` inteira é carregada de propósito: é ela que o media library lê em
 * getMedia(), então sem isso cada card dispararia uma query.
 */
final class AlbumFeed
{
    /** @return list<AlbumCard> */
    public function albums(): array
    {
        $albums = Album::query()
            ->published()
            ->withPhotos()
            ->chronological()
            ->with('media')
            ->withCount('photos')
            ->get();

        return array_values(
            $albums->map(fn (Album $album): AlbumCard => AlbumCard::fromAlbum($album))->all(),
        );
    }

    public function find(string $slug): ?AlbumDetail
    {
        $album = Album::query()
            ->published()
            ->withPhotos()
            ->where('slug', $slug)
            ->with(['media', 'event'])
            ->first();

        return $album instanceof Album ? AlbumDetail::fromAlbum($album) : null;
    }
}
