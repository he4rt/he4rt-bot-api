<section class="hp-section relative min-h-[calc(100svh-6rem)]!" id="community">
    <div
        class="pointer-events-none absolute -z-1 flex h-full w-full items-center justify-center overflow-hidden p-8 opacity-40 sm:p-16"
        aria-hidden="true"
    >
        <x-portal::animated-logo class="w-full max-w-5xl" />
    </div>
    <div class="hp-page hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="flex flex-col gap-4">
                <x-he4rt::headline size="2xl" :keywords="['potencial']">
                    <x-slot:badge>
                        <x-he4rt::badge>
                            <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5" />
                            Comunidade Open Source
                        </x-he4rt::badge>
                    </x-slot:badge>

                    <x-slot:title>
                        Desenvolva seu potencial na comunidade
                    </x-slot:title>

                    <x-slot:description class="font-normal">
                        Uma comunidade de desenvolvedores dedicada a ajudar iniciantes a se tornarem profissionais
                        através de projetos, mentorias e networking.
                    </x-slot:description>
                    <x-slot:actions>
                        <x-he4rt::button href="https://discord.gg/he4rt" icon="heroicon-s-chevron-right">
                            Começar agora
                        </x-he4rt::button>

                        <x-he4rt::button
                            href="https://github.com/he4rt"
                            icon="heroicon-s-chevron-right"
                            variant="outline"
                        >
                            Explorar projetos
                        </x-he4rt::button>
                    </x-slot:actions>
                </x-he4rt::headline>
                <div class="flex flex-col items-center gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <x-he4rt::avatar-stack :images="$this->avatars" :limit="count($this->avatars)" size="sm" />
                    <span class="text-text-medium text-center text-sm sm:text-left">
                        Mais de {{ number_format($this->usersCount, thousands_separator: '.') }} desenvolvedores já
                        fazem parte
                    </span>
                </div>
            </div>
            <div class="flex min-w-0 flex-col items-center justify-center gap-6">
                <x-portal::terminal :stats="$this->terminalStats" />

                <x-he4rt::card
                    href="/docs"
                    density="compact"
                    class="h-auto w-full max-w-md lg:max-w-lg rounded-[0_8px_8px_0] border-t-0 border-r-0 border-b-0 border-l-4 border-l-primary shadow-md shadow-text-dark/8 dark:shadow-text-light/3 bg-elevation-01dp dark:bg-elevation-surface"
                    aria-label="Abrir documentação da comunidade"
                >
                    <div class="flex items-center gap-4">
                        <div class="text-primary bg-primary/12 flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-current/15">
                            <x-filament::icon icon="heroicon-o-light-bulb" class="h-6 w-6" />
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <p class="text-text-high text-base font-semibold">
                                Novo por aqui?
                            </p>
                            <p class="text-text-medium text-sm">
                                Confira nossa documentação e descubra como participar.
                            </p>
                        </div>

                        <x-filament::icon
                            icon="heroicon-o-arrow-right"
                            class="text-text-medium h-5 w-5 shrink-0 self-center"
                        />
                    </div>
                </x-he4rt::card>
            </div>
        </div>
    </div>
</section>
