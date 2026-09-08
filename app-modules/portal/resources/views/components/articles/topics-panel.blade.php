@props([
    'topics',
    'total',
])

<div class="relative" x-on:keydown.escape.window="topicsOpen = false">
    <button
        type="button"
        x-on:click="topicsOpen = ! topicsOpen"
        x-bind:aria-expanded="topicsOpen ? 'true' : 'false'"
        x-bind:class="topic
            ? 'border-transparent bg-gradient-to-br from-primary to-secondary text-text-light'
            : 'border-outline-dark text-text-high hover:border-primary hover:bg-primary/10'"
        class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-xs font-medium transition-all duration-300 hover:scale-[1.02] active:scale-95"
        aria-controls="articles-topics-panel"
    >
        <span class="font-mono">#</span>
        temas
        <span x-show="topic" x-cloak style="display: none" class="font-mono tabular-nums">1</span>
        <span aria-hidden="true" class="text-[0.6rem]">▾</span>
    </button>

    <div
        id="articles-topics-panel"
        x-show="topicsOpen"
        x-cloak
        style="display: none"
        x-transition.opacity.duration.150ms
        x-on:click.outside="topicsOpen = false"
        x-ref="topicsPanel"
        {{-- Objeto, não string: `x-bind:style` com string vira setAttribute('style', ...)
             e apaga o `display: none` que o x-show escreve, deixando o painel aberto
             no load. Com objeto o Alpine escreve só a propriedade ligada. --}}
        x-bind:style="{ maxHeight: topicsMaxHeight + 'px' }"
        class="border-outline-low bg-elevation-05dp absolute inset-s-0 top-full z-50 mt-2 w-[min(22rem,calc(100vw-3rem))] overflow-y-auto overscroll-contain rounded-lg border p-2 shadow-lg shadow-text-high/18"
        style="scrollbar-width: thin"
    >
        <p class="text-text-medium px-2 pt-1 pb-2 font-mono text-[0.65rem] tracking-[0.15em] uppercase">
            {{ count($topics) }} temas · barra = fatia do acervo
        </p>

        @foreach ($topics as $entry)
            @php($share = round($entry->share($total) * 100))
            <button
                type="button"
                x-on:click="toggleTopic(@js($entry->tag)); topicsOpen = false"
                x-bind:aria-pressed="topic === @js($entry->tag) ? 'true' : 'false'"
                x-bind:class="{ 'bg-primary/16': topic === @js($entry->tag) }"
                class="hover:bg-primary/10 group flex w-full cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-start transition-colors duration-200"
            >
                <span class="text-text-high min-w-0 flex-1 truncate font-mono text-xs">#{{ $entry->tag }}</span>

                {{-- A barra é o argumento honesto: um tema presente em quase todo
                     artigo preenche a linha inteira e se denuncia como rótulo, não recorte. --}}
                <span aria-hidden="true" class="bg-outline-low/40 h-1 w-24 shrink-0 overflow-hidden rounded-full">
                    <span class="from-primary to-secondary block h-full rounded-full bg-gradient-to-r" style="width: {{ $share }}%"></span>
                </span>

                <span class="text-text-medium w-6 shrink-0 text-end font-mono text-xs tabular-nums">{{ $entry->count }}</span>
            </button>
        @endforeach
    </div>
</div>
