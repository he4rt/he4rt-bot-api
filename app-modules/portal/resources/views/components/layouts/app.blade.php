<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    {{-- title, viewport, description, canonical, Open Graph, favicon e JSON-LD: laravel/head (App\Support\Seo\SiteHead + metadata das rotas em PortalServiceProvider) --}}
    @head
    @vite (['app-modules/he4rt/resources/css/theme.css'])
    {{ Vite::fonts(['satoshi', 'fira-code']) }}
    @fluxAppearance
</head>
<body class="min-h-screen antialiased">
    <x-portal::navbar />

    {{ $slot }}
    @fluxScripts
</body>
</html>
