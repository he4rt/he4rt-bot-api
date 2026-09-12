@props (['title', 'noindex' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    @if ($noindex)
        <meta name="robots" content="noindex, nofollow" />
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" />

    <title>{{ isset($title) ? $title . ' - ' : null }} {{ config('app.name') }}</title>

    <!-- Fonts -->
    {{ Vite::fonts('nunito') }}

    <meta name="color-scheme" content="light dark" />

    @vite (['app-modules/docs/resources/css/theme.css'])

    <script>
        // Light é o padrão do portal: só fica dark se o usuário escolher dark no toggle.
        if (localStorage.getItem('flux.appearance') !== 'dark') {
            localStorage.setItem('flux.appearance', 'light');
        }
    </script>
    @fluxAppearance
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <a
        href="#conteudo"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-zinc-900 focus:shadow-lg dark:focus:bg-zinc-900 dark:focus:text-white"
    >
        Pular para o conteúdo
    </a>

    {{ $slot }}

    @fluxScripts
</body>
</html>
