@props(['articles'])

@if ($articles !== [])
    <section class="hp-section pb-10 lg:pb-14" id="artigos">
        <div class="hp-page hp-container">
            <x-he4rt::headline align="center" size="md" :keywords="['comunidade']">
                <x-slot:badge>
                    <x-he4rt::badge>
                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                        Learn in public
                    </x-he4rt::badge>
                </x-slot>

                <x-slot:title>O que a comunidade escreveu</x-slot>

                <x-slot:description>
                    Os artigos mais recentes publicados pela He4rt no dev.to, por quem faz parte daqui.
                </x-slot>
            </x-he4rt::headline>

            {{-- O card do acervo consulta isVisible() para o recorte da /artigos. Aqui
                 não há recorte: o escopo devolve sempre verdadeiro e o card fica intacto. --}}
            <div x-data="{ isVisible: () => true }" class="grid w-full grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($articles as $index => $article)
                    <x-portal::articles.card :article="$article" :index="$index" />
                @endforeach
            </div>

            <div class="flex justify-center">
                <x-he4rt::button
                    :href="route('articles', absolute: false)"
                    variant="outline"
                    icon="heroicon-s-arrow-right"
                    iconPosition="trailing"
                >
                    Ver todos os artigos
                </x-he4rt::button>
            </div>
        </div>
    </section>
@endif
