@php
    $events = $this->upcomingEvents;
@endphp

<div>
    <section class="hp-section" id="agenda">
        @if ($events->isNotEmpty())
            <script type="application/ld+json">
                {!! json_encode($this->schemaOrg, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}
            </script>
        @endif

        <div class="hp-container">
            <x-he4rt::headline size="md" align="center" :keywords="['eventos', 'He4rt']" title-tag="h2">
                <x-slot:title>Próximos eventos da comunidade He4rt</x-slot:title>

                <x-slot:description>
                    Eventos semanais e gratuitos de tecnologia para todos os níveis, de quem está começando agora a quem já trabalha na área. Participe de encontros, aulas, presencialmente ou online. Aprenda a programar, tire suas dúvidas e conecte-se com uma comunidade de desenvolvedores em crescimento.
                </x-slot:description>
            </x-he4rt::headline>

            @if ($events->isEmpty())
                <div class="mx-auto flex max-w-2xl flex-col items-center gap-4 rounded-2xl border border-outline-low/60 bg-elevation-surface px-6 py-12 text-center">
                    <div class="grid h-14 w-14 place-items-center rounded-full bg-primary/10 text-primary">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-7 w-7" />
                    </div>
                    <p class="text-text-high text-lg font-bold tracking-tight">
                        Nenhum evento agendado no momento
                    </p>
                    <p class="text-text-medium max-w-md text-sm">
                        A comunidade He4rt está sempre criando novos eventos, aulas e encontros. Fique de olho no nosso
                        Discord para saber quando o próximo for anunciado.
                    </p>
                    <x-he4rt::button
                        :href="'https://discord.gg/he4rt'"
                        target="_blank"
                        variant="solid"
                        icon="heroicon-s-arrow-right"
                        class="mt-2"
                    >
                        Entrar no Discord
                    </x-he4rt::button>
                </div>
            @else
            <div
                class="events-carousel relative w-full max-w-6xl"
                x-data="{
                    scrollable: false,
                    atStart: true,
                    atEnd: true,
                    dragging: false,
                    startX: 0,
                    startLeft: 0,
                    moved: false,
                    update() {
                        const t = $refs.track;
                        this.scrollable = t.scrollWidth > t.clientWidth + 2;
                        this.atStart = t.scrollLeft <= 2;
                        this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 2;
                    },
                    nudge(dir) {
                        const t = $refs.track;
                        const slide = t.querySelector('.events-slide');
                        const step = slide ? slide.offsetWidth + 24 : t.clientWidth * 0.8;
                        t.scrollBy({ left: dir * step, behavior: 'smooth' });
                    },
                    down(e) {
                        if (e.pointerType !== 'mouse') return;
                        e.preventDefault();
                        this.dragging = true;
                        this.moved = false;
                        this.startX = e.clientX;
                        this.startLeft = $refs.track.scrollLeft;
                    },
                    move(e) {
                        if (!this.dragging) return;
                        const dx = e.clientX - this.startX;
                        if (Math.abs(dx) > 4) this.moved = true;
                        $refs.track.scrollLeft = this.startLeft - dx;
                    },
                    up() {
                        this.dragging = false;
                    },
                    click(e) {
                        if (this.moved) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.moved = false;
                        }
                    },
                }"
                x-init="update(); $nextTick(() => update())"
                @resize.window="update()"
            >
                <button
                    type="button"
                    class="absolute top-1/2 left-0 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-outline-low bg-elevation-surface/90 text-text-high shadow-lg backdrop-blur transition-all duration-300 hover:border-primary hover:text-primary sm:-left-5"
                    :class="!scrollable || atStart ? 'pointer-events-none scale-75 opacity-0' : 'opacity-100'"
                    @click="nudge(-1)"
                    aria-label="Eventos anteriores"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                </button>

                <div
                    class="flex items-stretch gap-6 overflow-x-auto px-1 py-3 scroll-auto cursor-grab snap-x snap-proximity active:cursor-grabbing [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    x-ref="track"
                    @scroll.passive="update()"
                    @pointerdown="down($event)"
                    @pointermove="move($event)"
                    @pointerup.window="up()"
                    @pointercancel.window="up()"
                    @click.capture="click($event)"
                >
                    @foreach ($events as $item)
                        @php
                            $event = $item['event'];
                            $occurrence = $item['occurrence'];
                            $cover = $event->getFirstMedia('cover');
                            $offline = str_contains(mb_strtolower((string) $event->location), 'online') === false && $event->location !== null;
                        @endphp

                        <div class="events-slide shrink-0 snap-start basis-[clamp(280px,30%,400px)]">
                            <article
                                class="group relative flex h-full flex-col overflow-hidden rounded-[1.35rem] border border-outline-low/70 bg-elevation-surface/95 shadow-sm transition-all duration-500 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl"
                                x-data="{
                                    shareData: @js([
                                        'title' => $event->title,
                                        'text' => implode("\n", array_filter([
                                            $event->title,
                                            $event->description,
                                            $event->location,
                                            $occurrence->translatedFormat('d M Y H:i'),
                                        ])) . "\n\nSaiba mais -> " . url('/'),
                                    ]),
                                    copied: false,
                                    share() {
                                        if (navigator.share) {
                                            navigator.share(this.shareData).catch(() => {});
                                        } else if (navigator.clipboard) {
                                            navigator.clipboard.writeText(this.shareData.text);
                                            this.copied = true;
                                            setTimeout(() => (this.copied = false), 2000);
                                        }
                                    },
                                }"
                            >
                                <div class="relative h-36 shrink-0 overflow-hidden sm:h-40">
                                    <div class="absolute inset-0 h-full w-full">
                                        @if ($cover)
                                            <img
                                                src="{{ $cover->getUrl() }}"
                                                alt="Capa do evento {{ $event->title }}"
                                                @if ($cover->width) width="{{ $cover->width }}" @endif
                                                @if ($cover->height) height="{{ $cover->height }}" @endif
                                                loading="lazy"
                                                fetchpriority="low"
                                                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                            />
                                        @else
                                            <div
                                                class="from-primary/20 via-primary/5 to-elevation-surface flex h-full w-full items-center justify-center bg-gradient-to-br"
                                                aria-hidden="true"
                                            >
                                                <img
                                                    src="{{ asset('images/logo.svg') }}"
                                                    alt=""
                                                    class="h-3/5 w-auto opacity-25"
                                                />
                                            </div>
                                        @endif
                                    </div>

                                    <div
                                        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-elevation-surface via-elevation-surface/35 to-transparent"
                                        aria-hidden="true"
                                    ></div>

                                    <span
                                        class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3.5 py-1.5 text-xs font-bold tracking-wide text-primary uppercase shadow-sm backdrop-blur"
                                    >
                                        <x-filament::icon :icon="$offline ? 'heroicon-s-map-pin' : 'heroicon-s-signal'" class="h-4 w-4" />
                                        {{ $offline ? 'Presencial' : 'Online' }}
                                    </span>

                                    <button
                                        type="button"
                                        class="absolute top-4 right-4 z-10 grid h-10 w-10 place-items-center rounded-full bg-white/90 text-text-high shadow-sm backdrop-blur transition-colors hover:bg-primary hover:text-white"
                                        @click="share()"
                                        :title="copied ? 'Link copiado!' : 'Compartilhar evento'"
                                        aria-label="Compartilhar evento"
                                    >
                                        <x-filament::icon icon="heroicon-s-share" class="h-4 w-4" x-show="!copied" />
                                        <x-filament::icon icon="heroicon-s-check" class="h-4 w-4" x-cloak x-show="copied" />
                                    </button>

                                </div>

                                <div class="flex flex-1 flex-col gap-3 px-6 pt-4 pb-3">
                                    <time
                                        class="text-text-high flex items-center gap-2 text-sm font-semibold tracking-wide"
                                        datetime="{{ $occurrence->toIso8601String() }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-calendar-days" class="text-primary h-4 w-4" />
                                        {{ $occurrence->format('d') }} {{ strtoupper($occurrence->translatedFormat('M')) }}
                                        • {{ $occurrence->format('H:i') }}
                                    </time>

                                    <h3 class="text-text-high text-xl leading-tight font-bold tracking-tight">{{ $event->title }}</h3>

                                    @if ($event->description)
                                        <p class="text-text-medium text-sm leading-relaxed">{{ $event->description }}</p>
                                    @endif
                                </div>

                                <div class="mt-auto flex items-center justify-between gap-3 px-6 pb-5 pt-2">
                                    <div class="text-text-medium min-w-0">
                                        @if ($event->location)
                                            <p class="truncate text-sm">{{ $event->location }}</p>
                                        @endif
                                    </div>

                                    <x-he4rt::button
                                        :href="url('/app/events/'.$event->id)"
                                        variant="solid"
                                        size="sm"
                                        rounded="lg"
                                        icon="heroicon-s-arrow-right"
                                        class="!w-auto shrink-0 px-5 py-2.5 text-sm shadow-lg shadow-primary/20"
                                    >
                                        Participar
                                    </x-he4rt::button>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>

                <button
                    type="button"
                    class="absolute top-1/2 right-0 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-outline-low bg-elevation-surface/90 text-text-high shadow-lg backdrop-blur transition-all duration-300 hover:border-primary hover:text-primary sm:-right-5"
                    :class="!scrollable || atEnd ? 'pointer-events-none scale-75 opacity-0' : 'opacity-100'"
                    @click="nudge(1)"
                    aria-label="Próximos eventos"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </button>
            </div>
            @endif
        </div>
    </section>
</div>
