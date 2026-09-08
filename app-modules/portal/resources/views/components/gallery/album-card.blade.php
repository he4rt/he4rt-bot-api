@props(['album'])

{{-- O link do título cobre o card inteiro (after:inset-0); o resto é decoração. --}}
<article
    class="border-outline-low bg-elevation-01dp hover:border-primary/60 relative grid gap-5 rounded-lg border p-5 transition-[border-color] lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8"
>
    <div class="flex flex-col gap-2">
        <h2 class="text-text-high text-xl leading-tight font-bold">
            <a
                href="{{ route('gallery.album', ['slug' => $album->slug], absolute: false) }}"
                class="focus-visible:outline-primary after:absolute after:inset-0 after:rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2"
            >
                {{ $album->title }}
            </a>
        </h2>

        <p class="text-text-medium text-sm leading-relaxed">
            @if ($album->location)
                {{ $album->location }}
                <br />
            @endif
            <time datetime="{{ $album->happenedAt->toDateString() }}">{{ $album->happenedLabel() }}</time>
        </p>

        <span class="border-primary/32 bg-primary/5 text-primary mt-auto self-start rounded-md border px-2 py-0.5 font-mono text-xs">
            {{ $album->photoCountLabel() }}
        </span>
    </div>

    <div class="grid grid-cols-3 gap-3 sm:grid-cols-[repeat(3,minmax(0,1fr))_auto]">
        @foreach ($album->highlights as $photo)
            <figure class="relative overflow-hidden rounded-md">
                <img
                    src="{{ $photo->thumbUrl }}"
                    alt="{{ $photo->caption ?? '' }}"
                    loading="lazy"
                    decoding="async"
                    class="aspect-[4/3] w-full object-cover"
                />
                @if ($photo->caption)
                    <figcaption class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/70 to-transparent px-2 pt-6 pb-1.5 text-xs text-white">
                        {{ $photo->caption }}
                    </figcaption>
                @endif
            </figure>
        @endforeach

        @if ($album->remaining() > 0)
            <div
                aria-hidden="true"
                class="border-outline-low text-text-high flex aspect-[4/3] items-center justify-center rounded-md border border-dashed font-mono text-sm sm:aspect-auto sm:w-20"
            >
                +{{ $album->remaining() }}
            </div>
        @endif
    </div>
</article>
