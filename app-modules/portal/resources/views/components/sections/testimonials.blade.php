<section class="hp-section" id="testimonials">
    <div class="hp-page hp-container">
        <x-he4rt::headline align="center" size="md" :keywords="['membros']">
            <x-slot:badge>
                <x-he4rt::badge>
                    <x-filament::icon icon="heroicon-o-chat-bubble-oval-left-ellipsis" class="h-5 w-5" />
                    Depoimentos
                </x-he4rt::badge>
            </x-slot>

            <x-slot:title>O que dizem nossos membros</x-slot>

            <x-slot:description>
                Histórias reais de desenvolvedores que transformaram suas carreiras através da nossa comunidade.
            </x-slot>
        </x-he4rt::headline>

        <div class="grid w-full grid-cols-1 items-center justify-center gap-8 sm:grid-cols-3 lg:gap-12">
            <x-he4rt::card>
                <x-slot:description>
                    "A comunidade Coração Dev mudou completamente minha trajetória profissional. Através dos projetos e
                    mentorias, consegui meu primeiro emprego como desenvolvedor e hoje faço parte de uma empresa
                    incrível."
                </x-slot>
                <x-slot:footer>
                    <x-he4rt::partials.author
                        size="lg"
                        src="https://avatars.githubusercontent.com/u/103362"
                        name="Daniel Reis"
                        title="DevRel"
                    />
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card>
                <x-slot:description>
                    "A comunidade Coração Dev mudou completamente minha trajetória profissional. Através dos projetos e
                    mentorias, consegui meu primeiro emprego como desenvolvedor e hoje faço parte de uma empresa
                    incrível."
                </x-slot>
                <x-slot:footer>
                    <x-he4rt::partials.author
                        size="lg"
                        src="https://avatars.githubusercontent.com/u/103362"
                        name="Daniel Reis"
                        title="DevRel"
                    />
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card>
                <x-slot:description>
                    "A comunidade Coração Dev mudou completamente minha trajetória profissional. Através dos projetos e
                    mentorias, consegui meu primeiro emprego como desenvolvedor e hoje faço parte de uma empresa
                    incrível."
                </x-slot>
                <x-slot:footer>
                    <x-he4rt::partials.author
                        size="lg"
                        src="https://avatars.githubusercontent.com/u/103362"
                        name="Daniel Reis"
                        title="DevRel"
                    />
                </x-slot>
            </x-he4rt::card>
        </div>
    </div>
</section>
