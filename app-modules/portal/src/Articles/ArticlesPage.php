<?php

declare(strict_types=1);

namespace He4rt\Portal\Articles;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Página institucional do acervo de artigos.
 *
 * O componente só monta o read model. Recorte por tema e por pessoa e troca de
 * visualização vivem no Alpine: são estado de vitrine, não de servidor, e um
 * round-trip por clique só adicionaria latência.
 */
#[Layout(name: 'portal::components.layouts.app')]
final class ArticlesPage extends Component
{
    public function render(ArticleFeed $feed): View
    {
        return view('portal::articles', [
            'articles' => $feed->articles(),
            'authors' => $feed->authors(),
            'topics' => $feed->topics(),
            'stats' => $feed->stats(),
            'highlight' => $feed->highlight(),
        ]);
    }
}
