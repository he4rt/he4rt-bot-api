@props(['article'])

{{-- Acima da grade e na largura dela: o destaque abre a listagem em vez de
     disputar a primeira dobra com o título da página. --}}
<article
    class="bg-elevation-01dp shadow-sm shadow-text-high/8 hover:bg-elevation-02dp hover:shadow-md relative mb-6 flex flex-col gap-5 rounded-lg p-4 transition-[background-color,box-shadow,transform] duration-300 hover:scale-[1.02] motion-reduce:transition-none motion-reduce:hover:scale-100 sm:flex-row sm:items-center"
>
    @if ($article->coverImage)
        <img
            src="{{ $article->coverImage }}"
            alt=""
            loading="lazy"
            decoding="async"
            class="aspect-video w-full shrink-0 rounded-sm object-cover sm:w-72 lg:w-80"
        />
    @else
        <x-portal::articles.cover-fallback class="aspect-video w-full shrink-0 rounded-sm sm:w-72 lg:w-80" />
    @endif

    <div class="flex min-w-0 flex-col gap-2.5">
        <span
            class="bg-primary/5 text-text-medium w-fit rounded-full px-3 py-1 font-mono text-[0.65rem] tracking-wide"
        >
            <span class="text-primary">★</span> destaque · mais reagido dos últimos 12 meses
        </span>

        <h2 class="text-text-high line-clamp-2 text-xl leading-snug font-semibold lg:text-2xl">
            <a
                href="{{ $article->url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="after:absolute after:inset-0 focus-visible:outline-none"
            >
                {{ $article->title }}
            </a>
        </h2>

        @if ($article->description !== '')
            <p class="text-text-medium line-clamp-2 max-w-2xl text-sm leading-relaxed">{{ $article->description }}</p>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg px-4 py-3 text-[0.7rem] font-medium">
            <span class="flex min-w-0 items-center gap-1.5">
                <x-portal::articles.author-avatar :name="$article->authorName" :avatar="$article->authorAvatar" size="size-5" />
                <span class="text-text-medium truncate font-medium">{{ $article->authorName }}</span>
            </span>
            <span class="text-text-medium ms-auto flex items-center gap-2.5 font-mono tabular-nums">
                <span title="reações" class="flex items-center gap-1">
                    <x-heroicon-o-heart class="text-icon-medium size-3.5" />
                    {{ $article->reactions }}
                </span>
                <span title="tempo de leitura" class="flex items-center gap-1">
                    <x-heroicon-o-clock class="text-icon-medium size-3.5" />
                    {{ $article->readingMinutes }} min
                </span>
                <span title="publicado em" class="flex items-center gap-1">
                    <x-heroicon-o-calendar class="text-icon-medium size-3.5" />
                    {{ $article->publishedLabel() }}
                </span>
            </span>
        </div>
    </div>
</article>
