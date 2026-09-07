<main class="hp-page py-16">
    @vite('app-modules/portal/resources/js/live-player.js')

    <div wire:poll.10s="pulse" aria-live="polite">
        @if ($live === null)
            <div class="mx-auto flex w-full flex-col gap-6">
                <h1 class="text-text-high text-2xl font-bold text-balance sm:text-3xl">Live da He4rt</h1>

                <div class="border-outline-low bg-elevation-01dp flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-12 text-center">
                    <p class="text-text-high text-sm font-semibold">Nenhuma live no ar agora</p>
                    <p class="text-text-medium max-w-sm text-xs">
                        Esta página atualiza sozinha quando a transmissão começar.
                    </p>
                </div>
            </div>
        @else
            <div
                x-data="{ theater: false, chatCollapsed: false }"
                @keydown.window.escape="theater = false"
                x-effect="document.documentElement.classList.toggle('overflow-hidden', theater)"
                class="mx-auto flex w-full flex-col gap-6"
            >
                <div
                    class="flex"
                    :class="theater ? 'fixed inset-0 z-[100] gap-0 bg-black p-0' : 'relative flex-col gap-4'"
                >
                    <div
                        class="relative overflow-hidden rounded-xl bg-black"
                        :class="theater
                            ? (chatCollapsed ? 'h-full rounded-none mr-12' : 'h-full rounded-none mr-[20rem]')
                            : (chatCollapsed ? 'aspect-video lg:mr-12' : 'aspect-video lg:mr-[19rem]')"
                    >
                        @if ($status->onAir)
                            <video
                                data-live-player
                                data-hls-url="{{ config('live.hls_url') }}"
                                data-live-channel="{{ $live->id }}"
                                controls
                                autoplay
                                muted
                                playsinline
                                class="h-full w-full bg-black object-contain"
                            ></video>
                        @endif

                        <div
                            data-live-waiting
                            data-live-channel="{{ $live->id }}"
                            @class([
                                'bg-elevation-01dp absolute inset-0 flex flex-col items-center justify-center gap-2 p-12 text-center',
                                'hidden' => $status->onAir,
                            ])
                        >
                            <p class="text-text-high text-sm font-semibold">Aguardando sinal</p>
                            <p class="text-text-medium max-w-sm text-xs">
                                A transmissão volta sozinha assim que o sinal for restabelecido.
                            </p>
                        </div>

                        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-3 bg-gradient-to-b from-black/70 via-black/20 to-transparent p-4">
                            <div class="pointer-events-auto flex items-center gap-3">
                                @if ($status->onAir)
                                    <span class="flex w-fit items-center gap-2 rounded-full border border-red-500/40 bg-red-500/10 px-3 py-1.5 text-xs font-semibold text-red-400">
                                        <span class="relative flex h-2 w-2">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                                        </span>
                                        Ao vivo
                                    </span>
                                @endif

                                @if ($viewers > 0)
                                    <span class="rounded-full bg-black/40 px-2 py-1 text-xs font-medium text-white">{{ $viewers }} assistindo</span>
                                @endif
                            </div>

                            <button
                                type="button"
                                @click="theater = !theater"
                                class="pointer-events-auto rounded-full bg-black/40 p-2 text-white transition hover:bg-black/60"
                                :aria-label="theater ? 'Sair do modo teatro' : 'Modo teatro'"
                            >
                                <x-filament::icon x-show="!theater" icon="heroicon-o-film" class="h-4 w-4" />
                                <x-filament::icon x-show="theater" x-cloak icon="heroicon-o-arrows-pointing-in" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div
                        class="min-h-0 overflow-hidden"
                        :class="theater
                            ? (chatCollapsed ? 'absolute inset-y-0 right-0 w-12' : 'absolute inset-y-0 right-0 w-[20rem]')
                            : (chatCollapsed ? 'lg:absolute lg:inset-y-0 lg:right-0 lg:w-12' : 'lg:absolute lg:inset-y-0 lg:right-0 lg:w-[19rem]')"
                    >
                        <div x-show="!chatCollapsed" class="h-full min-h-0 overflow-hidden">
                            <livewire:portal.live-chat :live-id="$live->id" :key="$live->id" />
                        </div>

                        <button
                            x-show="chatCollapsed"
                            x-cloak
                            @click="chatCollapsed = false"
                            type="button"
                            class="border-outline-low bg-elevation-01dp text-text-medium hover:text-text-high flex h-24 w-full flex-col items-center justify-center gap-2 rounded-lg border py-4 lg:h-full"
                        >
                            <x-filament::icon icon="heroicon-o-chevron-left" class="h-4 w-4" />
                            <span class="text-xs font-semibold [writing-mode:vertical-rl]">Chat</span>
                        </button>
                    </div>
                </div>

                <div x-show="!theater" class="flex flex-col gap-2">
                    <h1 class="text-text-high text-2xl font-bold text-balance sm:text-3xl">{{ $live->title }}</h1>

                    @if ($live->description)
                        <p class="text-text-medium max-w-2xl text-sm">{{ $live->description }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</main>
