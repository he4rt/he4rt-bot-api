<?php

declare(strict_types=1);

namespace He4rt\Portal\Gallery;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Página pública da galeria: um card por álbum, do mais recente ao mais antigo.
 */
#[Layout(name: 'portal::components.layouts.app')]
final class GalleryPage extends Component
{
    public function render(AlbumFeed $feed): View
    {
        return view('portal::gallery', [
            'albums' => $feed->albums(),
        ]);
    }
}
