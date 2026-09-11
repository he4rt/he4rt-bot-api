<?php

declare(strict_types=1);

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * Painéis são áreas autenticadas e não devem aparecer em busca. O robots.txt
 * impede o rastreamento, mas uma URL de painel linkada de fora ainda poderia
 * ser indexada sem ser rastreada — a meta robots injetada pelo render hook do
 * Filament (App\Providers\FilamentServiceProvider) é o que fecha essa porta.
 */
it('marca as telas de painel como noindex', function (string $uri): void {
    get($uri)->assertOk()->assertSeeHtml('<meta name="robots" content="noindex, nofollow" />');
})->with([
    'login do admin' => '/admin/login',
    'login do app' => '/app/login',
]);

it('não marca o portal público como noindex', function (): void {
    get('/')->assertOk()->assertDontSeeHtml('content="noindex');
});
