@php
    $links = [
        [
            'label' => 'Artigos',
            'href' => route('articles', absolute: false),
            'active' => request()->routeIs('articles'),
        ],
        [
            'label' => 'Redes',
            'href' => route('social-links', absolute: false),
            'active' => request()->routeIs('social-links'),
        ],
    ];
@endphp

<header
    x-data="{
        open: false,
        scrolled: false,
        onScroll() {
            this.scrolled = window.scrollY > 24;
        },
    }"
    x-init="onScroll()"
    @scroll.window.passive="onScroll()"
    @keydown.escape.window="open = false"
    x-effect="document.documentElement.style.overflow = open ? 'hidden' : ''"
    class="hp-navbar sticky top-0 z-50 flex w-full"
>
    <div class="hp-page relative flex-1">
        {{-- glow roxo radial atrás da pill --}}
        <div
            aria-hidden="true"
            class="hp-navbar-glow pointer-events-none absolute inset-x-0 -top-3 -z-10 mx-auto h-20 max-w-4xl rounded-full blur-2xl"
        ></div>

        {{-- partículas sutis (twinkle lento) --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <span class="hp-navbar-star" style="--x: 10%; --y: 30%; --d: 0s"></span>
            <span class="hp-navbar-star" style="--x: 32%; --y: 65%; --d: 1.4s"></span>
            <span class="hp-navbar-star" style="--x: 58%; --y: 25%; --d: 2.7s"></span>
            <span class="hp-navbar-star" style="--x: 86%; --y: 60%; --d: 3.5s"></span>
        </div>

        {{-- a PILL de vidro --}}
        <nav
            :class="scrolled
                ? 'mt-2 bg-elevation-surface/80 py-3 dark:bg-zinc-900/80'
                : 'mt-4 bg-elevation-surface/55 py-4 dark:bg-zinc-900/60'"
            class="relative flex items-center justify-between gap-4 rounded-2xl px-4 shadow-md backdrop-blur-xl transition-all duration-300 dark:rounded-xl dark:shadow-white/5 md:px-6"
        >
            <x-portal::logo size="sm" />

            {{-- navegação + CTAs alinhados à direita (desktop) --}}
            <div class="hidden items-center gap-8 lg:flex">
                <div class="flex items-center gap-4">
                    @foreach ($links as $link)
                        <a
                            href="{{ $link['href'] }}"
                            @if ($link['active']) aria-current="page" @endif
                            class="relative border-b px-3 py-1.5 text-sm transition-all duration-200 hover:-translate-y-0.5 {{ $link['active'] ? 'text-text-high border-purple-700 font-semibold' : 'text-text-medium hover:text-text-high border-transparent font-medium' }}"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <x-he4rt::button
                        href="https://loja.heartdevs.com/he4rt/"
                        size="sm"
                        variant="outline"
                        icon="heroicon-o-shopping-bag"
                        iconPosition="leading"
                        aria-label="Loja"
                        target="_blank"
                        rel="noopener"
                        class="text-text-medium hover:text-text-high rounded-lg border-none bg-transparent px-3 py-2.5 font-medium hover:bg-transparent focus:ring-2 focus:ring-purple-600"
                    >
                        Loja
                    </x-he4rt::button>
                </div>

                <div class="flex items-center gap-3">
                    <x-he4rt::button
                        href="https://discord.gg/he4rt"
                        size="sm"
                        icon="fab-discord"
                        iconPosition="leading"
                        class="hp-navbar-static-icon dark:text-text-high rounded-lg bg-none bg-[#f3e8ff] px-4 py-2.5 text-sm font-medium text-purple-600 hover:bg-[#e9d5ff] focus:ring-2 focus:ring-purple-600 dark:bg-[#8525d8]/15 dark:hover:bg-[#8525d8]/25"
                    >
                        Discord
                    </x-he4rt::button>
                    <x-he4rt::button
                        href="/app"
                        size="sm"
                        variant="outline"
                        class="text-text-light focus:ring-offset-elevation-surface rounded-lg border-none bg-purple-600 px-4 py-2.5 text-sm font-semibold shadow-sm hover:bg-purple-700 focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                    >
                        Área do Usuário
                    </x-he4rt::button>
                </div>
            </div>

            {{-- hamburguer (mobile) --}}
            <button
                type="button"
                @click="open = true"
                class="text-text-high inline-flex items-center justify-center rounded-full p-2 ring-1 ring-zinc-950/8 transition hover:bg-zinc-950/5 dark:ring-white/10 dark:hover:bg-white/5 lg:hidden"
                :aria-expanded="open.toString()"
                aria-controls="mobile-drawer"
                aria-label="Abrir menu"
            >
                <x-filament::icon icon="heroicon-o-bars-3" class="h-5 w-5" />
            </button>
        </nav>
    </div>

    {{-- scrim (mobile) --}}
    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- drawer (mobile) --}}
    <aside
        id="mobile-drawer"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="bg-elevation-surface/85 fixed inset-y-0 right-0 z-50 flex w-[82%] max-w-sm flex-col gap-6 overflow-y-auto rounded-l-3xl border-l border-zinc-950/10 p-6 shadow-2xl shadow-zinc-950/20 backdrop-blur-xl dark:border-white/15 dark:shadow-black/50 lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Menu de navegação"
    >
        <div class="flex items-center justify-between border-b border-zinc-950/10 pb-4 dark:border-white/10">
            <span class="text-text-medium text-sm font-semibold tracking-wide">Menu</span>
            <button
                type="button"
                @click="open = false"
                class="text-text-high rounded-full p-2 ring-1 ring-zinc-950/8 transition hover:bg-zinc-950/5 dark:ring-white/10 dark:hover:bg-white/5"
                aria-label="Fechar menu"
            >
                <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
            </button>
        </div>

        {{-- links com stagger reveal --}}
        <nav class="flex flex-col gap-1">
            @foreach ($links as $i => $link)
                <a
                    href="{{ $link['href'] }}"
                    @click="open = false"
                    x-show="open"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    style="transition-delay: {{ 60 * $i }}ms"
                    @if ($link['active']) aria-current="page" @endif
                    class="flex items-center justify-between rounded-xl px-3 py-3 text-base font-medium transition-colors {{ $link['active'] ? 'bg-zinc-950/5 text-text-high dark:bg-white/5' : 'text-text-medium hover:bg-zinc-950/5 hover:text-text-high dark:hover:bg-white/5' }}"
                >
                    {{ $link['label'] }}
                    @if ($link['active'])
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--primary)]"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- CTAs full-width --}}
        <div class="flex flex-col gap-3 border-t border-zinc-950/10 pt-6 dark:border-white/10">
            <x-he4rt::button
                href="https://loja.heartdevs.com/he4rt/"
                variant="outline"
                block
                icon="heroicon-s-shopping-bag"
                iconPosition="leading"
                target="_blank"
                rel="noopener"
            >
                Compre na Loja
            </x-he4rt::button>
            <x-he4rt::button href="/app" variant="outline" block> Área do Usuário </x-he4rt::button>
            <x-he4rt::button
                href="https://discord.gg/he4rt"
                block
                icon="fab-discord"
                iconPosition="leading"
                class="hp-navbar-static-icon"
            >
                Entrar no Discord
            </x-he4rt::button>
        </div>
    </aside>
</header>

<style>
    .hp-navbar-glow {
        background: radial-gradient(
            60% 120% at 50% 0%,
            color-mix(in oklab, var(--primary) 40%, transparent),
            transparent 70%
        );
        animation: hpNavbarGlow 6s ease-in-out infinite;
    }

    @keyframes hpNavbarGlow {
        0%,
        100% {
            opacity: 0.45;
        }
        50% {
            opacity: 0.85;
        }
    }

    .hp-navbar-star {
        position: absolute;
        top: var(--y, 30%);
        left: var(--x, 50%);
        width: 2px;
        height: 2px;
        border-radius: 9999px;
        background: color-mix(in srgb, var(--text-high) 45%, transparent);
        opacity: 0.3;
        animation: hpNavbarTwinkle 4s ease-in-out infinite;
        animation-delay: var(--d, 0s);
    }

    @keyframes hpNavbarTwinkle {
        0%,
        100% {
            opacity: 0.12;
            transform: scale(0.8);
        }
        50% {
            opacity: 0.7;
            transform: scale(1.2);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .hp-navbar-glow,
        .hp-navbar-star {
            animation: none !important;
        }
    }

    .hp-navbar-static-icon:hover .hp-button-icon {
        transform: none;
    }
</style>
