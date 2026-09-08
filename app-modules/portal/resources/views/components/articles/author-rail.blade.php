@props([
    'authors',
])

@php
    // Posto de cada pessoa quando a coluna é ordenada por alcance. A reordenação
    // acontece por `order` no flex, o que preserva a renderização no servidor.
    $byReactions = collect($authors)->sortByDesc(fn ($author): int => $author->reactions)->values();
    $reactionRank = $byReactions->mapWithKeys(fn ($author, int $rank): array => [$author->username => $rank])->all();
@endphp

<aside class="flex flex-col gap-3" aria-labelledby="articles-authors-heading">
    <h2 id="articles-authors-heading" class="text-text-medium font-mono text-xs tracking-[0.2em] uppercase">
        Quem escreve
    </h2>

    {{-- Volume e alcance divergem no acervo: quem publicou uma vez pode ter mais
         reações que quem publicou quatro. Por isso as duas ordens são oferecidas. --}}
    <div class="flex items-center gap-1" role="group" aria-label="Ordenar pessoas">
        @foreach ([['articles', 'artigos'], ['reactions', 'reações']] as [$mode, $label])
            <button
                type="button"
                x-on:click="authorSort = '{{ $mode }}'"
                x-bind:aria-pressed="authorSort === '{{ $mode }}' ? 'true' : 'false'"
                x-bind:class="authorSort === '{{ $mode }}'
                    ? 'border-transparent bg-gradient-to-br from-primary to-secondary text-text-light'
                    : 'border-outline-dark text-text-medium hover:border-primary hover:bg-primary/10'"
                class="flex-1 cursor-pointer rounded-md border px-2 py-1.5 text-xs font-medium transition-all duration-300 active:scale-95"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="text-text-low mt-2 flex items-center justify-end gap-2 pe-2 font-medium text-[0.7rem]">
        <span class="flex w-14 items-center justify-end gap-1">
            <x-heroicon-o-document-text class="size-3" />
            artigos
        </span>
        <span class="flex w-14 items-center justify-end gap-1">
            <x-heroicon-o-heart class="text-primary dark:text-purple-400 size-3" />
            reações
        </span>
    </div>

    <div class="bg-elevation-02dp flex flex-col gap-2 rounded-lg p-3">
        <ul class="flex flex-col">
            @foreach ($authors as $author)
                <li x-bind:style="{ order: authorSort === 'reactions' ? {{ $reactionRank[$author->username] }} : {{ $loop->index }} }">
                    <button
                        type="button"
                        x-on:click="toggleAuthor(@js($author->username))"
                        x-bind:aria-pressed="author === @js($author->username) ? 'true' : 'false'"
                        x-bind:class="{ 'bg-primary/16': author === @js($author->username) }"
                        class="hover:bg-primary/10 text-text-high flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-start transition-colors duration-200"
                    >
                        <x-portal::articles.author-avatar :name="$author->name" :avatar="$author->avatar" size="size-7" />
                        <span class="min-w-0 flex-1 truncate text-xs font-medium">{{ $author->name }}</span>
                        <span class="flex shrink-0 items-center gap-0.5 font-mono font-medium text-xs tabular-nums">
                            <span class="text-text-low w-14 text-end">{{ $author->articleCount }}</span>
                            <span class="text-primary dark:text-purple-400 w-14 text-end">{{ $author->reactions }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

        <p class="text-text-low border-outline-low border-t pt-3 font-mono text-[0.65rem]">
            {{ count($authors) }} pessoas no acervo
        </p>
    </div>
</aside>
