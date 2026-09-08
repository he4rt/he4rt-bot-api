@props([
    'article',
    'index',
])

<article
    x-show="isVisible({{ $index }})"
    class="bg-elevation-02dp border-primary/16 has-[a:focus-visible]:ring-primary relative flex flex-col overflow-hidden rounded-l-none rounded-r-lg border-l-2 shadow-sm shadow-text-high/8 transition-[background-color,box-shadow,transform] duration-200 ease-out hover:-translate-y-0.75 hover:bg-elevation-03dp hover:shadow-md has-[a:focus-visible]:ring-2 motion-reduce:transition-none motion-reduce:hover:translate-y-0 [.is-list_&]:flex-row"
>
    <div class="shrink-0 [.is-list_&]:w-40 sm:[.is-list_&]:w-56">
        @if ($article->coverImage)
            <img
                src="{{ $article->coverImage }}"
                alt=""
                loading="lazy"
                decoding="async"
                class="aspect-video w-full object-cover [.is-list_&]:aspect-auto [.is-list_&]:h-full"
            />
        @else
            <x-portal::articles.cover-fallback class="aspect-video w-full [.is-list_&]:aspect-auto [.is-list_&]:h-full" />
        @endif
    </div>

    <div class="flex min-w-0 flex-1 flex-col px-4 pt-3.5 pb-4">
        <h3 class="text-text-high line-clamp-3 text-sm leading-snug font-medium">
            <a
                href="{{ $article->url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="after:absolute after:inset-0 focus-visible:outline-none"
            >
                {{ $article->title }}
            </a>
        </h3>

        {{-- A descrição já vem truncada da fonte; o clamp evita que o "…" dela pareça quebra de layout. --}}
        <p class="text-text-medium mt-1 line-clamp-2 text-xs leading-relaxed">{{ $article->description }}</p>

        @if ($article->tags !== [])
            <ul class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach (array_slice($article->tags, 0, 2) as $tag)
                    <li
                        class="bg-text-high/6 text-text-medium rounded-md px-2 py-1 font-mono text-[0.75rem] leading-none"
                    >
                        #{{ $tag }}
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- O pt-4 garante respiro claro entre as tags e o rodapé; o mt-auto alinha o rodapé entre cards de alturas diferentes. --}}
        <div class="mt-auto flex items-center gap-2 pt-4 text-[0.7rem] font-medium">
            <x-portal::articles.author-avatar :name="$article->authorName" :avatar="$article->authorAvatar" size="size-4" />
            <span class="text-text-medium truncate font-medium">{{ $article->authorName }}</span>

            <span class="text-text-medium ms-auto flex shrink-0 items-center gap-2.5 font-mono tabular-nums">
                <span title="reações" class="flex items-center gap-1">
                    <x-heroicon-o-heart class="text-icon-medium size-3.5" />
                    {{ $article->reactions }}
                </span>
                <span title="comentários" class="flex items-center gap-1">
                    <x-heroicon-o-chat-bubble-left-ellipsis class="text-icon-medium size-3.5" />
                    {{ $article->comments }}
                </span>
                <span title="tempo de leitura" class="flex items-center gap-1">
                    <x-heroicon-o-clock class="text-icon-medium size-3.5" />
                    {{ $article->readingMinutes }} min
                </span>
            </span>
        </div>
    </div>
</article>
