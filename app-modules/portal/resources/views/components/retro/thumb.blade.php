{{--
    Um slide do deck em miniatura, para o filmstrip do Deck Builder.

    O slide é renderizado em tamanho de deck (1280x720) e encolhido por `scale`:
    é a mesma partial, com as mesmas proporções, só menor — uma miniatura que
    reflowasse num container pequeno mostraria um layout que não existe (ADR-0002).

    `inert` tira a árvore inteira do foco e do ponteiro: os slides têm botões e
    links (carrossel, CTA) que não podem competir com o clique de selecionar.
--}}
@props(['view' => null, 'props' => []])

<div {{ $attributes->class('retro-thumb') }} inert>
    <div class="retro-thumb-stage">
        <div class="retro">
            <div class="deck">
                @if (filled($view))
                    @includeIf($view, $props)
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
    </div>
</div>
