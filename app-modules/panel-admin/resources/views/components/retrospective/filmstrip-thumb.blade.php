{{--
    Uma célula da tira: a miniatura do slide, o botão que a seleciona e o rótulo.

    O botão COBRE a miniatura em vez de envolvê-la. Slides têm botões e links por
    dentro (carrossel, CTA), e um <button> não pode conter conteúdo interativo —
    envolver produziria markup inválido e um alvo de clique disputado.
--}}
@props([
    'selection',
    'index' => null,
    'label' => '',
    'muted' => false,
    'view' => null,
    'props' => [],
])

<div class="shrink-0" @if ($index !== null) data-deck-index="{{ $index }}" @endif>
    <div class="relative">
        @if (blank($view) && $slot->isEmpty())
            {{-- Kind do catálogo que não rendeu nada neste recorte: não há slide
                 para desenhar, mas o cartão fica. É ele que mostra que o slide
                 existe e que dá acesso ao on/off do kind. --}}
            <div
                class="flex aspect-video items-center justify-center rounded-lg border border-dashed border-gray-300 px-2 text-center text-[0.7rem] leading-tight text-gray-400 dark:border-white/10 dark:text-gray-500"
                style="width: calc(var(--retro-thumb-width, 208) * 1px)"
            >
                Sem dado neste recorte
            </div>
        @else
            <x-portal::retro.thumb
                :view="$view"
                :props="$props"
                @class(['opacity-40 grayscale' => $muted])
            >{{ $slot }}</x-portal::retro.thumb>
        @endif

        <button
            type="button"
            x-on:click="$dispatch('filmstrip-call', { method: 'select', args: ['{{ $selection }}'] })"
            aria-label="Inspecionar {{ $label }}"
            @class([
                'absolute inset-0 rounded-lg transition',
                {{-- Slide oculto não está no deck: nunca é o slide atual, então
                     o anel dele é estático e o Alpine não precisa saber que existe. --}}
                'ring-1 ring-black/5 hover:ring-2 hover:ring-primary-400 dark:ring-white/10' => $index === null,
            ])
            @if ($index !== null)
                :class="active === {{ $index }}
                    ? 'ring-2 ring-primary-500 ring-offset-2 ring-offset-white dark:ring-offset-gray-900'
                    : 'ring-1 ring-black/5 hover:ring-2 hover:ring-primary-400 dark:ring-white/10'"
            @endif
        ></button>
    </div>

    <div
        style="width: calc(var(--retro-thumb-width, 208) * 1px)"
        @class([
            'mt-1 truncate text-[0.7rem] font-medium',
            'text-gray-400 line-through dark:text-gray-600' => $muted,
            'text-gray-600 dark:text-gray-300' => ! $muted,
        ])
        title="{{ $label }}"
    >{{ $label }}</div>
</div>
