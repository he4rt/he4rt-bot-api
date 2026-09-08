@props([
    'topics',
    'total',
])

{{-- Barra de controle do acervo. Fica sticky dentro da coluna de artigos: como o
     containing block de um grid item é a própria área da grid, promovê-la a filha
     direta do grid faria o sticky morrer em silêncio. --}}
<div class="bg-elevation-surface/93 sticky top-0 z-30 -mx-1 mt-10 mb-4 px-1 pt-2 pb-3 backdrop-blur-xl">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <h2 class="text-text-medium font-mono text-xs tracking-[0.2em] uppercase">Artigos</h2>

        <x-portal::articles.topics-panel :topics="$topics" :total="$total" />

        <span class="text-text-medium font-mono text-xs">↓ mais recentes</span>

        <p class="text-text-medium ms-auto font-mono text-xs tabular-nums" aria-live="polite">
            <span class="text-text-high font-semibold" x-text="visibleCount">{{ $total }}</span>
            de {{ $total }} artigos
        </p>

        <div class="flex items-center gap-1" role="group" aria-label="Modo de visualização">
            @foreach ([['grid', 'Grade', 'heroicon-o-squares-2x2'], ['list', 'Lista', 'heroicon-o-bars-3']] as [$mode, $label, $icon])
                <button
                    type="button"
                    x-on:click="view = '{{ $mode }}'"
                    x-bind:aria-pressed="view === '{{ $mode }}' ? 'true' : 'false'"
                    x-bind:class="view === '{{ $mode }}'
                        ? 'border-transparent bg-gradient-to-br from-primary to-secondary text-text-light'
                        : 'border-outline-dark text-text-medium hover:border-primary hover:bg-primary/10'"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-md border transition-all duration-300 hover:scale-[1.02] active:scale-95"
                    aria-label="{{ $label }}"
                    title="{{ $label }}"
                >
                    <x-filament::icon :icon="$icon" class="h-4 w-4" />
                </button>
            @endforeach
        </div>
    </div>

    {{-- Segunda linha: só existe quando há recorte ativo. --}}
    <div class="mt-4 flex flex-wrap items-center gap-2" x-show="hasFilters" x-cloak style="display: none">
        <template x-if="author">
            <button
                type="button"
                x-on:click="author = null"
                class="border-primary/32 bg-primary/5 text-text-high hover:bg-primary/32 inline-flex cursor-pointer items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs font-medium transition-all duration-300 active:scale-95"
            >
                <span x-text="authorName(author)"></span>
                <span aria-hidden="true">×</span>
                <span class="sr-only">Remover filtro de autor</span>
            </button>
        </template>

        <template x-if="topic">
            <button
                type="button"
                x-on:click="topic = null"
                class="border-primary/32 bg-primary/5 text-text-high hover:bg-primary/32 inline-flex cursor-pointer items-center gap-2 rounded-xl border px-2.5 py-1.5 font-mono text-xs font-medium transition-all duration-300 active:scale-95"
            >
                <span x-text="'#' + topic"></span>
                <span aria-hidden="true">×</span>
                <span class="sr-only">Remover filtro de tema</span>
            </button>
        </template>

        <button
            type="button"
            x-on:click="clearAll()"
            class="text-text-medium hover:text-text-high cursor-pointer text-xs underline underline-offset-4 transition-colors"
        >
            limpar tudo
        </button>
    </div>
</div>
