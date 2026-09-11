<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    {{-- title, viewport, description, canonical, Open Graph, favicon e JSON-LD: laravel/head (App\Support\Seo\SiteHead + metadata das rotas em PortalServiceProvider) --}}
    @head
    @vite (['app-modules/portal/resources/css/retrospective.css'])
    {{ Vite::fonts(['fraunces', 'hanken-grotesk', 'jetbrains-mono']) }}
    @fluxAppearance
</head>
<body class="antialiased" style="margin: 0; background: #0b0a10">
    {{ $slot }}
    @fluxScripts
</body>
</html>
