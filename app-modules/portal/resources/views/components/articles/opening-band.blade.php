@props(['stats'])

<section class="relative overflow-hidden pt-10 pb-8 lg:pt-14">
    {{-- glow da marca: assinatura do portal, sob o conteúdo --}}
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-x-0 -top-24 -z-10 mx-auto h-64 max-w-4xl rounded-full opacity-60 blur-3xl"
        style="background: radial-gradient(60% 120% at 50% 0%, color-mix(in oklab, var(--primary) 40%, transparent), transparent 70%)"
    ></div>

    <div class="hp-page">
        <div class="flex max-w-3xl flex-col gap-5">
            <p class="text-text-medium flex items-center gap-3 font-mono text-xs tracking-[0.2em] uppercase">
                <span aria-hidden="true" class="bg-outline-low inline-block h-px w-8"></span>
                Learn in public · acervo no dev.to
            </p>

            <h1 class="text-text-high text-4xl leading-[1.08] font-bold tracking-tight lg:text-5xl">
                O que a <span class="text-primary">He4rt</span> escreveu, e quem escreveu.
            </h1>

            <p class="text-text-medium max-w-xl text-base leading-relaxed">
                Os artigos ao centro, quem escreve à direita, os temas a um clique na barra. Filtre por pessoa ou
                por tema e leve o recorte no link.
            </p>

            <dl class="text-text-medium flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                @foreach ([['articles', 'artigos'], ['authors', 'autores'], ['topics', 'temas'], ['reactions', 'reações']] as [$key, $label])
                    <div class="flex items-baseline gap-2">
                        <dt class="sr-only">{{ $label }}</dt>
                        <dd class="text-text-high font-mono text-lg font-semibold tabular-nums">
                            {{ number_format($stats[$key], 0, ',', '.') }}
                        </dd>
                        <span aria-hidden="true">{{ $label }}</span>
                    </div>
                    @if (! $loop->last)
                        <span aria-hidden="true" class="text-text-low">·</span>
                    @endif
                @endforeach
            </dl>
        </div>

    </div>
</section>
