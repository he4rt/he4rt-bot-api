<section class="hp-section" id="projects">
    <div class="hp-page hp-container">
        <x-he4rt::headline align="center" size="md" :keywords="['prática']">
            <x-slot:badge>
                <x-he4rt::badge>
                    <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                    Nossos projetos
                </x-he4rt::badge>
            </x-slot>

            <x-slot:title>Aprenda na prática</x-slot>

            <x-slot:description>
                Contribua com projetos open source e desenvolva habilidades reais enquanto constrói seu portfólio.
            </x-slot>
        </x-he4rt::headline>

        <div class="grid w-full grid-cols-1 items-center justify-center gap-8 sm:grid-cols-3 lg:gap-16">
            <x-he4rt::card>
                <x-slot:title>4Noobs</x-slot>
                <x-slot:description>Repositórios de conteúdos sobre diversas tecnologias.</x-slot>
                <x-slot:tags>
                    <x-he4rt::tag>Documentação</x-he4rt::tag>
                </x-slot>
                <x-slot:actions>
                    <x-he4rt::button href="https://github.com/he4rt/4noobs" block>Ver projeto</x-he4rt::button>
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card>
                <x-slot:title>He4rtLabs</x-slot>
                <x-slot:description>
                    Projetos práticos desenvolvidos pela comunidade para aprendizado.
                </x-slot>
                <x-slot:tags>
                    <x-he4rt::tag>Projetos</x-he4rt::tag>
                    <x-he4rt::tag>Documentação</x-he4rt::tag>
                </x-slot>
                <x-slot:actions>
                    <x-he4rt::button href="https://github.com/he4rt/heartlabs-challenges" block>
                        Ver projeto
                    </x-he4rt::button>
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card>
                <x-slot:title>100DiasDeCodigo</x-slot>
                <x-slot:description>
                    Plataforma para acompanhar o desafio dos 100 dias de código.
                </x-slot>
                <x-slot:tags>
                    <x-he4rt::tag>Documentação</x-he4rt::tag>
                    <x-he4rt::tag>Comunidade</x-he4rt::tag>
                </x-slot>
                <x-slot:actions>
                    <x-he4rt::button href="https://github.com/he4rt/100diasdecodigo" block>Ver projeto</x-he4rt::button>
                </x-slot>
            </x-he4rt::card>
        </div>
    </div>
</section>
