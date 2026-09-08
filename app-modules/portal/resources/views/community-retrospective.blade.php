@php ($noData = count($sources) === 0)
@use(He4rt\Portal\Retrospective\AboutSection)
@use(He4rt\Portal\Retrospective\SlideView)
<x-portal::retro.deck :stateKey="$stateKey" :bare="$noData">
    @if ($noData)
        <x-portal::retro.slides.empty />
    @else
        {{-- Uma capa por público: a de retrospectiva abre com balanço, a de
             onboarding recebe quem chegou. O resto do deck é o mesmo. --}}
        @include(SlideView::cover($coverKind), [
            'since' => $since,
            'until' => $until,
            'edition' => $edition ?? null,
            'hosts' => $hosts ?? [],
            'coverTitle' => $coverTitle ?? null,
            'coverIntro' => $coverIntro ?? null,
        ])

        {{-- Quem a He4rt é, antes dos números de quem ela fez. Seção fixa: não
             vem do snapshot nem passa pelo ComposeDeck, então nenhuma curadoria
             a desliga — ela é o contexto que faz o resto significar algo. --}}
        @foreach (AboutSection::slides() as $about)
            @include($about->view(), ['sources' => $sources, 'since' => $since, 'until' => $until])
        @endforeach

        @foreach ($sources as $source)
            @foreach ($source->slides as $slide)
                @include(SlideView::kind($slide->kind()), $slide->toArray())
            @endforeach
        @endforeach

        {{-- O ritual da tag, entre os números e o fecho. Posição fixa: tudo que
             veio antes é a prova do que se afirma aqui, então este bloco não é
             arrastável — só ligável e desligável (PromotionSection). --}}
        @foreach ($promotions as $promotion)
            @include($promotion->view(), ['cards' => $promotion->cards])
        @endforeach

        <x-portal::retro.slides.closing
            :sources="$sources"
            :since="$since"
            :until="$until"
            :closingText="$closingText ?? null"
        />
    @endif
</x-portal::retro.deck>
