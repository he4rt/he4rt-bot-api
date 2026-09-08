<?php

declare(strict_types=1);

namespace He4rt\Portal\Gallery;

use He4rt\Portal\Gallery\DTOs\AlbumDetail;
use He4rt\Portal\Gallery\DTOs\PhotoView;
use Illuminate\Contracts\View\View;
use Laravel\Head\Facades\Head;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Um álbum inteiro, com o lightbox. Rascunho e álbum sem foto não existem
 * para quem está de fora: os dois respondem 404.
 *
 * O Livewire só serializa propriedades públicas escalares, então a página
 * guarda o slug e resolve o DTO a cada render.
 */
#[Layout(name: 'portal::components.layouts.app')]
final class AlbumPage extends Component
{
    #[Locked]
    public string $slug;

    private ?AlbumDetail $album = null;

    public function mount(string $slug, AlbumFeed $feed): void
    {
        $this->slug = $slug;

        $album = $this->album($feed);

        // O <head> desta rota depende do álbum, então é montado aqui e não
        // no withHead() da rota.
        Head::title($album->title)
            ->description($this->description($album));

        if ($album->cover instanceof PhotoView) {
            Head::ogImage($album->cover->largeUrl, alt: $album->title);
        }
    }

    public function render(AlbumFeed $feed): View
    {
        return view('portal::gallery-album', [
            'album' => $this->album($feed),
        ]);
    }

    private function album(AlbumFeed $feed): AlbumDetail
    {
        $this->album ??= $feed->find($this->slug);

        abort_unless($this->album instanceof AlbumDetail, 404);

        return $this->album;
    }

    private function description(AlbumDetail $album): string
    {
        if ($album->description !== null && $album->description !== '') {
            return $album->description;
        }

        return sprintf(
            'Fotos do %s, %s. %s da comunidade He4rt Developers.',
            $album->title,
            $album->happenedLabel(),
            $album->photoCountLabel(),
        );
    }
}
