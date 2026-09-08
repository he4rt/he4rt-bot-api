<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * O modular carrega os arquivos de rota dos módulos SEM grupo de middleware, então
 * declarar `web` é obrigação de quem registra (ver identity/routes). Sem ele não há
 * StartSession, e o guard do preview (`auth()->check()` no mount) reprova todo mundo
 * com 403 no navegador.
 *
 * Um teste com `actingAs()` NÃO pega isso: o helper seta o usuário direto no
 * container, sem passar por sessão. Daí estes testes olharem o middleware e o
 * cookie de sessão, não o corpo da resposta.
 */
it('serve as rotas do portal dentro do grupo web', function (string $name): void {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('web');
})->with([
    'community.retrospective',
    'community.retrospective.preview',
    'gallery',
    'gallery.album',
    'social-links',
]);

it('inicia sessão na página pública, para o login do operador ser visível no preview', function (): void {
    $response = test()->get(route('community.retrospective'));

    $cookies = array_map(
        static fn (Cookie $cookie): string => $cookie->getName(),
        $response->headers->getCookies(),
    );

    expect($cookies)->toContain(config('session.cookie'));
});
