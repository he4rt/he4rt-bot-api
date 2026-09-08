@php
    // Payload mínimo para o recorte no cliente: quem assina e quais temas cada
    // artigo carrega. Os cards já vêm renderizados do servidor — o Alpine só
    // decide o que fica visível.
    $feedItems = collect($articles)
        ->map(fn ($article): array => [
            'a' => $article->authorUsername,
            'n' => $article->authorName,
            't' => $article->tags,
        ])
        ->values()
        ->all();
@endphp

<div x-data="articlesFeed(@js($feedItems))" class="pb-20">
    <x-portal::articles.opening-band :stats="$stats" />

    <div class="hp-page">
        @if ($articles !== [] && $highlight)
            {{-- Mesmas colunas do grid abaixo, só para o destaque herdar a largura
                 da coluna de artigos em vez de ocupar a página inteira. --}}
            <div class="grid lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-7">
                <x-portal::articles.featured :article="$highlight" />
            </div>
        @endif

        <div class="grid gap-7 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0">
                @if ($articles === [])
                    {{-- O catálogo é preenchido pelo sync; até a primeira rodada rodar, a
                         página diz o que houve em vez de fingir que ninguém escreveu. --}}
                    <div class="border-outline-low bg-elevation-01dp flex flex-col items-center gap-3 rounded-lg border border-dashed p-12 text-center">
                        <p class="text-text-high text-sm font-semibold">Ainda não há artigos por aqui.</p>
                        <p class="text-text-medium max-w-sm text-xs">
                            O acervo da comunidade é publicado no dev.to. Enquanto ele não aparece aqui, vá direto
                            para a organização.
                        </p>
                        <x-he4rt::button href="https://dev.to/he4rt" target="_blank" rel="noopener" size="sm">
                            Abrir no dev.to
                        </x-he4rt::button>
                    </div>
                @else
                <x-portal::articles.toolbar :topics="$topics" :total="count($articles)" />

                {{-- A grade é o padrão do servidor; o Alpine só troca para lista. Assim a
                     página nasce com layout correto mesmo antes (ou sem) o JS. --}}
                <div
                    class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(260px,1fr))]"
                    x-bind:class="view === 'list' ? 'is-list !grid-cols-1' : ''"
                >
                    @foreach ($articles as $index => $article)
                        <x-portal::articles.card :article="$article" :index="$index" />
                    @endforeach
                </div>

                <div
                    x-show="visibleCount === 0"
                    x-cloak
                    style="display: none"
                    class="border-outline-low bg-elevation-01dp mt-4 flex flex-col items-center gap-3 rounded-lg border border-dashed p-10 text-center"
                >
                    <p class="text-text-high text-sm font-semibold">Nenhum artigo com essa combinação.</p>
                    <p class="text-text-medium max-w-sm text-xs">
                        O tema e a pessoa selecionados não se cruzam no acervo. As duas listas seguem ativas — dá para
                        trocar o recorte sem sair daqui.
                    </p>
                    <button
                        type="button"
                        x-on:click="clearAll()"
                        class="from-primary to-secondary text-text-light cursor-pointer rounded-md bg-gradient-to-br px-4 py-2 text-xs font-semibold transition-all duration-300 hover:scale-[1.02] active:scale-95"
                    >
                        limpar tudo
                    </button>
                </div>
                @endif
            </div>

            {{-- No telefone a coluna de pessoas vai para baixo do feed: empilhar 17 linhas
                 acima dos cards custaria a dobra inteira. --}}
            @if ($authors !== [])
                <div class="order-last lg:sticky lg:top-4 lg:order-0 lg:mt-8 lg:self-start">
                    <x-portal::articles.author-rail :authors="$authors" />
                </div>
            @endif
        </div>
    </div>

    {{-- Componente class-based: o Livewire 4 exige @assets/@script para JS de componente.
         @assets é o bloco certo aqui — roda uma vez por página e antes dos scripts,
         que é o que o registro de Alpine.data() precisa para existir quando o Alpine sobe. --}}
    @assets
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('articlesFeed', (items) => ({
                items,
                author: null,
                topic: null,
                view: 'grid',
                authorSort: 'articles',
                topicsOpen: false,
                topicsMaxHeight: 420,

                // Recorte compartilhável: sem isso, "olha os artigos da Cherry" não cabe
                // num link. Os valores são conferidos contra o acervo antes de aplicar,
                // para um parâmetro inventado não deixar a página num estado vazio.
                syncUrl() {
                    const url = new URL(window.location.href)
                    this.author ? url.searchParams.set('autor', this.author) : url.searchParams.delete('autor')
                    this.topic ? url.searchParams.set('tema', this.topic) : url.searchParams.delete('tema')
                    window.history.replaceState({}, '', url)
                },

                readUrl() {
                    const params = new URLSearchParams(window.location.search)
                    const autor = params.get('autor')
                    const tema = params.get('tema')

                    if (autor && this.items.some((item) => item.a === autor)) this.author = autor
                    if (tema && this.items.some((item) => item.t.includes(tema))) this.topic = tema
                },

                init() {
                    this.readUrl()
                    this.$watch('author', () => this.syncUrl())
                    this.$watch('topic', () => this.syncUrl())

                    this.$watch('topicsOpen', (open) => open && this.$nextTick(() => this.measurePanel()))
                    window.addEventListener('resize', () => this.measurePanel(), { passive: true })
                    window.addEventListener('scroll', () => this.topicsOpen && this.measurePanel(), { passive: true })

                    // As webfontes chegam depois e empurram a faixa de abertura: sem
                    // remedir aqui, a primeira medida do painel mente.
                    if (document.fonts && document.fonts.ready) {
                        document.fonts.ready.then(() => this.measurePanel())
                    }
                },

                measurePanel() {
                    const panel = this.$refs.topicsPanel
                    if (!panel) return
                    const top = panel.getBoundingClientRect().top
                    this.topicsMaxHeight = Math.max(180, window.innerHeight - top - 16)
                },

                matchesFilters(index) {
                    const item = this.items[index]
                    if (this.author && item.a !== this.author) return false
                    if (this.topic && !item.t.includes(this.topic)) return false
                    return true
                },

                isVisible(index) {
                    return this.matchesFilters(index)
                },

                get visibleCount() {
                    return this.items.reduce((total, _, index) => total + (this.matchesFilters(index) ? 1 : 0), 0)
                },

                get hasFilters() {
                    return this.author !== null || this.topic !== null
                },

                clearAll() {
                    this.author = null
                    this.topic = null
                },

                toggleAuthor(username) {
                    this.author = this.author === username ? null : username
                },

                toggleTopic(tag) {
                    this.topic = this.topic === tag ? null : tag
                },

                authorName(username) {
                    const item = this.items.find((candidate) => candidate.a === username)
                    return item ? item.n : username
                },
            }))
        })
    </script>
    @endassets
</div>
