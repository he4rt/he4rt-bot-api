<x-filament-panels::page.simple>
    <div class="fi-login-split flex min-h-dvh">
        {{-- Left: Brand panel --}}
        <div
            class="relative hidden w-[480px] shrink-0 overflow-hidden lg:flex lg:flex-col lg:items-center lg:justify-center"
            style="background: linear-gradient(165deg, #782bf1 0%, #3b1578 45%, #18181b 100%)"
        >
            {{-- Glow accents --}}
            <div
                class="absolute top-0 right-0 h-64 w-64 rounded-full blur-3xl"
                style="background: #9b59f5; animation: login-pulse-glow 4s ease-in-out infinite"
            ></div>
            <div
                class="absolute bottom-0 left-0 h-48 w-48 rounded-full blur-3xl"
                style="background: #782bf1; animation: login-pulse-glow 4s ease-in-out infinite 2s"
            ></div>

            {{-- Landing logo as background watermark --}}
            <div class="absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('images/landingLogo.svg') }}" alt="" class="h-[420px] w-auto" aria-hidden="true" />
            </div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col items-center px-12">
                <div style="animation: login-float 6s ease-in-out infinite">
                    <svg
                        class="mb-8 h-32 w-auto drop-shadow-2xl"
                        viewBox="0 0 600 513"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M445.237 0.00033551C424.91 -0.0347431 404.777 3.89304 385.996 11.5576C367.216 19.2221 350.159 30.4719 335.808 44.6594L153.391 224.398L116.915 188.45C111.983 183.761 108.048 178.15 105.341 171.946C102.633 165.741 101.207 159.067 101.145 152.314C101.084 145.56 102.388 138.862 104.983 132.611C107.577 126.359 111.409 120.68 116.255 115.904C121.101 111.128 126.864 107.352 133.207 104.795C139.55 102.239 146.347 100.953 153.2 101.014C160.052 101.074 166.825 102.48 173.12 105.148C179.416 107.816 185.109 111.694 189.867 116.555L262.856 44.6594C233.71 16.6424 194.537 1.07109 153.824 1.31914C113.11 1.56719 74.1349 17.6146 45.3431 45.9846C16.5513 74.3546 0.261527 112.762 0.0031216 152.886C-0.255283 193.01 15.5385 231.618 43.9626 260.346L153.391 368.189L511.948 14.8274C491.12 5.01981 468.32 -0.0474973 445.237 0.00033551Z"
                            fill="white"
                        />
                        <path
                            d="M584.9 86.7579L445.237 224.433L408.76 188.45L335.808 260.345L372.284 296.293L226.379 440.084L299.332 512.015L554.665 260.381C577.296 238.07 592.355 209.395 597.769 178.303C603.183 147.21 598.687 115.228 584.9 86.7579Z"
                            fill="white"
                        />
                    </svg>
                </div>

                <h1 class="mb-2 text-2xl font-bold tracking-tight text-white">He4rt Developers</h1>
                <p class="text-center text-sm leading-relaxed text-white/60">Comunidade brasileira de desenvolvedores.<br />
                Aprenda, compartilhe e cresça.</p>

                <div class="mt-10 flex gap-8">
                    <div class="text-center">
                        <div class="text-xl font-bold text-white">5k+</div>
                        <div class="text-xs text-white/40">membros</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-white">200+</div>
                        <div class="text-xs text-white/40">eventos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-white">50+</div>
                        <div class="text-xs text-white/40">projetos</div>
                    </div>
                </div>
            </div>

            <div
                class="absolute right-0 bottom-0 left-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"
            ></div>
        </div>

        {{-- Right: Login form --}}
        <div class="flex flex-1 items-center justify-center p-8">
            <div class="w-full max-w-sm">
                {{-- Mobile logo --}}
                <div class="mb-8 flex items-center justify-center lg:hidden">
                    <img
                        src="{{ asset('images/logo.svg') }}"
                        alt="He4rt Developers"
                        class="h-12 w-auto dark:brightness-0 dark:invert"
                    />
                </div>

                <h2 class="mb-1 text-xl font-semibold dark:text-white text-dark">Entrar</h2>
                <p class="mb-6 text-sm text-zinc-400">Acesse sua conta He4rt Developers</p>

                {{-- OAuth --}}
                <div
                    class="grid gap-2.5"
                    x-data="{
                        lastProvider: null,
                        init() {
                            try {
                                const provider = window.localStorage.getItem('lastAuthProvider');
                                this.lastProvider = ['discord', 'github', 'twitch'].includes(provider) ? provider : null;
                            } catch (error) {
                                this.lastProvider = null;
                            }
                        },
                    }"
                >
                    <a
                        href="{{ route('oauth.redirect', ['panel' => 'app', 'provider' => 'discord']) }}"
                        class="relative flex items-center justify-center gap-2.5 rounded-lg bg-[#5865F2] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#4752C4]"
                    >
                        @svg ('fab-discord', 'h-5 w-5')
                        Continuar com Discord
                        <span
                            x-cloak
                            x-show="lastProvider === 'discord'"
                            class="absolute -top-2 right-2 z-10 rounded-full border border-zinc-600 bg-zinc-900/95 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-zinc-300 shadow-sm"
                        >
                            Último acesso
                        </span>
                    </a>

                    <a
                        href="{{ route('oauth.redirect', ['panel' => 'app', 'provider' => 'github']) }}"
                        class="relative flex items-center justify-center gap-2.5 rounded-lg bg-zinc-800 px-4 py-2.5 text-sm font-medium text-white ring-1 ring-zinc-700 transition hover:bg-zinc-700"
                    >
                        @svg ('fab-github', 'h-5 w-5')
                        Continuar com GitHub
                        <span
                            x-cloak
                            x-show="lastProvider === 'github'"
                            class="absolute -top-2 right-2 z-10 rounded-full border border-zinc-600 bg-zinc-900/95 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-zinc-300 shadow-sm"
                        >
                            Último acesso
                        </span>
                    </a>

                    <a
                        href="{{ route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch']) }}"
                        class="relative flex items-center justify-center gap-2.5 rounded-lg bg-[#9146FF] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#7B2FF0]"
                    >
                        @svg ('fab-twitch', 'h-5 w-5')
                        Continuar com Twitch
                        <span
                            x-cloak
                            x-show="lastProvider === 'twitch'"
                            class="absolute -top-2 right-2 z-10 rounded-full border border-zinc-600 bg-zinc-900/95 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-zinc-300 shadow-sm"
                        >
                            Último acesso
                        </span>
                    </a>
                </div>

                {{-- Divider --}}
                <div class="flex items-center gap-3 my-6">
                    <hr class="flex-1 border-t border-zinc-800">
                    <span class="text-xs uppercase text-zinc-500 dark:text-zinc-400">ou</span>
                    <hr class="flex-1 border-t border-zinc-800">
                </div>

                {{-- Filament email/password form --}}
                {{ $this->content }}
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
