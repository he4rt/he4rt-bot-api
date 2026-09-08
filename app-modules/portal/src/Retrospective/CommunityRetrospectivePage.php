<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use He4rt\Community\Retrospective\Models\Retrospective;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Página pública da retrospectiva. O visitante só assiste: monta a edição
 * publicada mais recente a partir do snapshot congelado, sem filtros e sem tocar
 * as fontes (ADR-0002). Em modo preview (rota autenticada), monta uma edição
 * específica — coletando ao vivo se ainda for rascunho — pelo mesmo render path,
 * então "ver rascunho" bate com o que será publicado.
 */
#[Layout(name: 'portal::components.layouts.deck')]
final class CommunityRetrospectivePage extends Component
{
    public ?string $retrospectiveId = null;

    public bool $preview = false;

    public function mount(?Retrospective $retrospective = null): void
    {
        if ($retrospective instanceof Retrospective) {
            // Preview de uma edição específica é só para operadores autenticados:
            // rascunhos não podem vazar pela URL pública.
            abort_unless(auth()->check(), 403);

            $this->retrospectiveId = $retrospective->id;
            $this->preview = true;
        }
    }

    public function render(): View
    {
        return view(
            'portal::community-retrospective',
            DeckPresentation::for($this->resolveEdition(), live: $this->preview),
        );
    }

    private function resolveEdition(): ?Retrospective
    {
        if ($this->retrospectiveId !== null) {
            return Retrospective::query()->find($this->retrospectiveId);
        }

        return Retrospective::query()->published()->latest('published_at')->first();
    }
}
