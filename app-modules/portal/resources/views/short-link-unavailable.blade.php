{{--
    The single answer for every dead /l/{slug}: unknown, disabled, expired and
    soft deleted. Nothing here may vary by case — a different message for each
    would let anyone scan slugs and learn which ones exist.
--}}
@php
    $discordUrl = config()->string('he4rt.social_media.discord.url');
@endphp

<x-portal::layouts.app>
    <main
        class="relative mx-auto flex min-h-[calc(100svh-6rem)] w-full max-w-xl flex-col items-center justify-center px-6 py-16 text-center"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center overflow-hidden opacity-10"
        >
            <x-portal::animated-logo class="w-[min(70vh,120vw)] max-w-xl" />
        </div>

        <p class="text-text-medium text-xs font-semibold tracking-[0.3em] uppercase">He4rt Devs</p>

        <h1 class="text-text-high mt-3 text-2xl font-bold text-balance sm:text-3xl">
            Esse link não está mais disponível
        </h1>

        <p class="text-text-medium mt-4 max-w-md text-sm text-pretty">
            O endereço curto que você abriu não leva mais a lugar nenhum — pode ter sido desligado, ter vencido ou
            nunca ter existido. A comunidade, essa continua no mesmo lugar de sempre.
        </p>

        <div class="mt-8 flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:items-center">
            <x-he4rt::button :href="route('home')" variant="outline" block class="sm:w-auto">
                Ir pra home
            </x-he4rt::button>

            <x-he4rt::button
                :href="$discordUrl"
                block
                icon="fab-discord"
                iconPosition="leading"
                target="_blank"
                rel="noopener noreferrer"
                class="sm:w-auto"
            >
                Entrar no Discord
            </x-he4rt::button>
        </div>
    </main>
</x-portal::layouts.app>
