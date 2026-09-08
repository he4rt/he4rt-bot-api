<section class="hp-section" id="contact">
    <div class="hp-page hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="border-outline-low flex flex-1 flex-col gap-8 rounded-lg border p-8">
                <x-he4rt::headline size="md">
                    <x-slot:title>Entre em contato conosco</x-slot>
                    <x-slot:description>
                        Histórias reais de desenvolvedores que transformaram suas carreiras através da nossa comunidade.
                    </x-slot>
                </x-he4rt::headline>

                <hr class="border-outline-low" />

                <x-he4rt::input label="Nome completo" />
                <x-he4rt::input label="Email" />
                <x-he4rt::textarea label="Mensagem" />

                <x-he4rt::button>Enviar mensagem</x-he4rt::button>
            </div>

            <div class="flex h-full flex-col gap-8">
                <div class="flex h-full flex-col gap-16">
                    <div class="relative flex flex-1 flex-col items-center justify-center">
                        <div class="absolute flex h-full w-full">
                            <img
                                src="{{ asset('images/contact-image.svg') }}"
                                alt="contact-image"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <x-he4rt::headline align="center" size="md">
                            <x-slot:title>Venha fazer parte do nosso discord</x-slot>
                            <x-slot:description>
                                Histórias reais de desenvolvedores que transformaram suas carreiras através da nossa
                                comunidade.
                            </x-slot>
                            <x-slot:actions>
                                <x-he4rt::button href="https://discord.com/invite/he4rt">
                                    Entrar no Discord
                                </x-he4rt::button>
                            </x-slot>
                        </x-he4rt::headline>
                    </div>

                    <div class="border-outline-low flex flex-col gap-4 rounded-lg border p-8">
                        <x-he4rt::headline class="mx-0" size="sm">
                            <x-slot:title>Redes sociais</x-slot>
                            <x-slot:description>
                                Siga-nos nas redes sociais para ficar por dentro das últimas novidades e conteúdos.
                            </x-slot>
                            <x-slot:actions>
                                <a href="https://discord.com/invite/he4rt">
                                    <x-filament::icon
                                        icon="fab-discord"
                                        class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                    />
                                </a>
                                <a href="https://www.linkedin.com/company/he4rt/">
                                    <x-filament::icon
                                        icon="fab-linkedin"
                                        class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                    />
                                </a>
                                <a href="https://x.com/He4rtDevs">
                                    <x-filament::icon
                                        icon="fab-x-twitter"
                                        class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                    />
                                </a>
                                <a href="https://instagram.com/heartdevs">
                                    <x-filament::icon
                                        icon="fab-instagram"
                                        class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                    />
                                </a>
                                <a href="https://github.com/he4rt/">
                                    <x-filament::icon
                                        icon="fab-github"
                                        class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                    />
                                </a>
                            </x-slot>
                        </x-he4rt::headline>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
