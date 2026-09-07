<div x-data="galleryLightbox(@js($album->lightboxPayload()))" class="pb-20">
    <section class="relative overflow-hidden pt-10 pb-8 lg:pt-14">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 -top-24 -z-10 mx-auto h-64 max-w-4xl rounded-full opacity-60 blur-3xl"
            style="background: radial-gradient(60% 120% at 50% 0%, color-mix(in oklab, var(--primary) 40%, transparent), transparent 70%)"
        ></div>

        <div class="hp-page">
            <div class="flex max-w-3xl flex-col gap-5">
                <a
                    href="{{ route('gallery', absolute: false) }}"
                    class="text-text-medium hover:text-text-high flex items-center gap-2 self-start font-mono text-xs tracking-[0.2em] uppercase transition-colors"
                >
                    <x-filament::icon icon="heroicon-s-arrow-left" class="h-3.5 w-3.5" />
                    Galeria
                </a>

                <h1 class="text-text-high text-4xl leading-[1.08] font-bold tracking-tight lg:text-5xl">
                    {{ $album->title }}
                </h1>

                <p class="text-text-medium flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <time datetime="{{ $album->happenedAt->toDateString() }}">{{ $album->happenedLabel() }}</time>
                    @if ($album->location)
                        <span aria-hidden="true" class="text-text-low">·</span>
                        <span>{{ $album->location }}</span>
                    @endif
                    @if ($album->eventTitle)
                        <span aria-hidden="true" class="text-text-low">·</span>
                        <span>{{ $album->eventTitle }}</span>
                    @endif
                    <span aria-hidden="true" class="text-text-low">·</span>
                    <span class="font-mono">{{ $album->photoCountLabel() }}</span>
                </p>

                @if ($album->description)
                    <p class="text-text-medium max-w-xl text-base leading-relaxed">{{ $album->description }}</p>
                @endif
            </div>
        </div>
    </section>

    <div class="hp-page">
        <div class="grid gap-3 [grid-template-columns:repeat(auto-fill,minmax(180px,1fr))] sm:[grid-template-columns:repeat(auto-fill,minmax(220px,1fr))]">
            @foreach ($album->photos as $index => $photo)
                <button
                    type="button"
                    x-on:click="openAt({{ $index }})"
                    class="group focus-visible:outline-primary relative overflow-hidden rounded-md focus-visible:outline-2 focus-visible:outline-offset-2"
                    aria-label="Abrir foto {{ $index + 1 }} de {{ $album->photoCount() }}"
                >
                    <img
                        src="{{ $photo->thumbUrl }}"
                        alt="{{ $photo->caption ?? '' }}"
                        loading="{{ $index < 8 ? 'eager' : 'lazy' }}"
                        decoding="async"
                        class="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                    />
                    @if ($photo->caption)
                        <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/70 to-transparent px-2 pt-6 pb-1.5 text-left text-xs text-white">
                            {{ $photo->caption }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <x-portal::gallery.lightbox />

    @assets
        <script>
            document.addEventListener('alpine:init', () => {
                // O lightbox lê a foto pela URL (?foto=N, base 1) para um link
                // compartilhado abrir direto na imagem certa. replaceState evita
                // encher o histórico: fechar o lightbox não pode virar "voltar".
                Alpine.data('galleryLightbox', (photos) => ({
                    photos,
                    open: false,
                    index: 0,
                    touchStartX: null,

                    init() {
                        const requested = Number.parseInt(new URLSearchParams(window.location.search).get('foto'), 10)

                        if (Number.isInteger(requested) && requested >= 1 && requested <= this.photos.length) {
                            this.openAt(requested - 1)
                        }
                    },

                    get current() {
                        return this.photos[this.index] ?? { l: '', c: null }
                    },

                    get hasMany() {
                        return this.photos.length > 1
                    },

                    openAt(index) {
                        this.index = index
                        this.open = true
                        this.syncUrl()
                        this.preload(index + 1)
                    },

                    close() {
                        this.open = false
                        this.syncUrl()
                    },

                    next() {
                        if (!this.open || !this.hasMany) return
                        this.index = (this.index + 1) % this.photos.length
                        this.syncUrl()
                        this.preload(this.index + 1)
                    },

                    prev() {
                        if (!this.open || !this.hasMany) return
                        this.index = (this.index - 1 + this.photos.length) % this.photos.length
                        this.syncUrl()
                        this.preload(this.index - 1)
                    },

                    syncUrl() {
                        const url = new URL(window.location.href)
                        this.open ? url.searchParams.set('foto', String(this.index + 1)) : url.searchParams.delete('foto')
                        window.history.replaceState(window.history.state, '', url)
                    },

                    preload(index) {
                        const photo = this.photos[(index + this.photos.length) % this.photos.length]
                        if (!photo) return
                        const image = new Image()
                        image.src = photo.l
                    },

                    touchStart(event) {
                        this.touchStartX = event.changedTouches[0].clientX
                    },

                    touchEnd(event) {
                        if (this.touchStartX === null) return
                        const delta = event.changedTouches[0].clientX - this.touchStartX
                        this.touchStartX = null
                        if (delta > 40) this.prev()
                        else if (delta < -40) this.next()
                    },
                }))
            })
        </script>
    @endassets
</div>
