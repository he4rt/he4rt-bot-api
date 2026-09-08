@php
    $usersImages = [
        'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1527980965255-d3b416303d12?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1527980965255-d3b416303d12?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
    ];
@endphp

<section class="hp-section relative" id="community">
    <div class="absolute -z-1 flex h-[150%] w-[150%] sm:h-full sm:w-full sm:p-16">
        <img src="{{ asset('images/landingLogo.svg') }}" alt="Logo" class="h-full w-full" />
    </div>
    <div class="hp-page hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="flex flex-col gap-4">
                <x-he4rt::headline size="2xl" :keywords="['potencial']">
                    <x-slot:badge>
                        <x-he4rt::badge>
                            <x-filament::icon icon="heroicon-o-book-open" class="text-icon-light h-5 w-5" />
                            Comunidade Open Source
                        </x-he4rt::badge>
                    </x-slot>

                    <x-slot:title>Desenvolva seu potencial na comunidade</x-slot>

                    <x-slot:description>
                        Uma comunidade de desenvolvedores dedicada a ajudar iniciantes a se tornarem profissionais
                        através de projetos, mentorias e networking.
                    </x-slot>
                    <x-slot:actions>
                        <x-he4rt::button href="https://discord.com/invite/he4rt" icon="heroicon-s-chevron-right">
                            Começar agora
                        </x-he4rt::button>

                        <x-he4rt::button href="#projects" icon="heroicon-s-chevron-right" variant="outline">
                            Explorar projetos
                        </x-he4rt::button>
                    </x-slot>
                </x-he4rt::headline>
                <x-he4rt::avatar-stack :images="$usersImages" limit="5">
                    Mais de 9.000 desenvolvedores já fazem parte
                </x-he4rt::avatar-stack>
            </div>
            <div class="flex flex-col items-center justify-center">
                <div class="mx-auto flex w-full max-w-lg flex-col">
                    <div class="overflow-hidden rounded-lg bg-gray-900 shadow-xl">
                        <div class="flex items-center gap-2 bg-gray-800 p-4">
                            <div class="h-3 w-3 rounded-full bg-red-500"></div>
                            <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                            <div class="h-3 w-3 rounded-full bg-green-500"></div>
                        </div>

                        <div class="flex w-full flex-col gap-2 p-6 font-mono text-sm text-gray-300">
                            <div class="flex items-center">
                                <span class="mr-2 text-gray-500">$</span>
                                <span class="text-gray-100">he4rtdevs.exe</span>
                            </div>
                            <div class="mt-1 flex items-center">
                                <span class="mr-2 text-gray-500">$</span>
                                <span class="text-gray-100">he4rt --init</span>
                            </div>
                            <div class="mt-2">
                                <span class="text-gray-400">Conectando recursos...</span>
                            </div>
                            <div class="mt-1">
                                <span class="text-gray-400">Preparando ambiente...</span>
                            </div>
                            <div class="mt-2">
                                <span class="text-green-400">Bem-vindo à comunidade Coração.dev!</span>
                            </div>
                            <div class="mt-4">
                                <span class="text-cyan-400">Recursos disponíveis</span>
                            </div>
                            <div class="mt-2 pl-2">
                                <div class="flex items-center">
                                    <span class="mr-2 text-gray-500">*</span>
                                    <span>Projetos open-source</span>
                                </div>
                                <div class="mt-1 flex items-center">
                                    <span class="mr-2 text-gray-500">*</span>
                                    <span>Comunidade ativa</span>
                                </div>
                                <div class="mt-1 flex items-center">
                                    <span class="mr-2 text-gray-500">*</span>
                                    <span>Mentorias</span>
                                </div>
                                <div class="mt-1 flex items-center">
                                    <span class="mr-2 text-gray-500">*</span>
                                    <span>Eventos e WorkShops</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
