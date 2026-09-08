<div class="pb-20">
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
                    Galeria · fotos dos encontros
                </p>

                <h1 class="text-text-high text-4xl leading-[1.08] font-bold tracking-tight lg:text-5xl">
                    O que acontece quando a gente sai do <span class="text-primary">Discord</span>.
                </h1>

                <p class="text-text-medium max-w-xl text-base leading-relaxed">
                    Meetups, workshops, pubs e confraternizações da He4rt Developers, álbum por álbum. Abra um
                    e veja quem estava lá.
                </p>
            </div>
        </div>
    </section>

    <div class="hp-page flex flex-col gap-6">
        @if ($albums === [])
            {{-- A galeria é preenchida pelo painel. Até o primeiro álbum sair, a página
                 diz isso em vez de ficar em branco. --}}
            <div class="border-outline-low bg-elevation-01dp flex flex-col items-center gap-3 rounded-lg border border-dashed p-12 text-center">
                <p class="text-text-high text-sm font-semibold">Ainda não há álbuns por aqui.</p>
                <p class="text-text-medium max-w-sm text-xs">
                    Os próximos encontros vão aparecer nesta página. Enquanto isso, acompanhe a agenda pelas
                    nossas redes.
                </p>
                <x-he4rt::button :href="route('social-links', absolute: false)" size="sm" variant="outline">
                    Ver nossas redes
                </x-he4rt::button>
            </div>
        @else
            @foreach ($albums as $album)
                <x-portal::gallery.album-card :album="$album" />
            @endforeach
        @endif
    </div>
</div>
