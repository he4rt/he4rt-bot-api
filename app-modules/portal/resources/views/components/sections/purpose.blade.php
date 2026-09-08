<section class="hp-section" id="about">
    <div class="hp-page hp-container">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-16">
            <div class="order-2 grid grid-cols-1 gap-8 sm:order-1 sm:grid-cols-2">
                <x-he4rt::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary/8 border-primary/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-hashtag" class="text-primary h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Comunidade</x-slot>
                    <x-slot:description>
                        Mais de 2.000 membros ativos compartilhando conhecimento.
                    </x-slot>
                </x-he4rt::card>
                <x-he4rt::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary/8 border-primary/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="text-primary h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Interação</x-slot>
                    <x-slot:description>
                        Workshops, lives e encontros virtuais com especialistas.
                    </x-slot>
                </x-he4rt::card>
                <x-he4rt::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary/8 border-primary/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-code-bracket" class="text-primary h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Aprendizado</x-slot>
                    <x-slot:description>
                        Dezenas de projetos open source para contribuir e aprender.
                    </x-slot>
                </x-he4rt::card>
                <x-he4rt::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary/8 border-primary/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-user-group" class="text-primary h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Colaboração</x-slot>
                    <x-slot:description>
                        Orientação personalizada para acelerar seu desenvolvimento.
                    </x-slot>
                </x-he4rt::card>
            </div>
            <div class="order-1 flex flex-col items-center justify-center sm:order-2">
                <x-he4rt::headline size="md" :keywords="['jornada']">
                    <x-slot:badge>
                        <x-he4rt::badge>
                            <x-filament::icon icon="heroicon-o-cursor-arrow-ripple" class="h-5 w-5" />
                            Nossa missão
                        </x-he4rt::badge>
                    </x-slot>

                    <x-slot:title>Transformando a jornada em desenvolvimento</x-slot>

                    <x-slot:description>
                        Nascemos da necessidade de unir pessoas que compartilham do mesmo propósito
                        <span class="text-text-high font-bold">aprender desenvolvimento</span>
                        e
                        <span class="text-text-high font-bold">ajudar desenvolvedores a crescerem juntos.</span>

                        <br />

                        Nossa comunidade é formada por desenvolvedores de todos os níveis, desde iniciantes até
                        profissionais experientes, que colaboram em projetos open source, compartilham conhecimento e
                        criam oportunidades.
                    </x-slot>
                    <x-slot:actions>
                        <x-he4rt::button
                            href="https://discord.com/invite/he4rt"
                            icon="heroicon-s-chevron-right"
                            variant="outline"
                        >
                            Participe da nossa comunidade
                        </x-he4rt::button>
                    </x-slot>
                </x-he4rt::headline>
            </div>
        </div>
    </div>
</section>
