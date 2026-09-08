<?php

declare(strict_types=1);

use He4rt\Portal\SocialLinks\SocialLinksPage;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

it('responde 200 em /redes', function (): void {
    get('/redes')->assertOk();
});

it('resolve a rota nomeada social-links para /redes', function (): void {
    expect(route('social-links', absolute: false))->toBe('/redes');
});

it('renderiza todos os links com rótulos e URLs corretas', function (): void {
    $assertion = get('/redes')
        ->assertOk();

    foreach (SocialLinksPage::links() as $link) {
        $assertion->assertSee($link->label)
            ->assertSee($link->url);
    }

});

it('abre cada link externo em nova aba com rel seguro', function (): void {
    $html = get('/redes')->getContent();

    expect(mb_substr_count($html, 'target="_blank"'))->toBe(9);
    expect(mb_substr_count($html, 'rel="noopener noreferrer"'))->toBe(7);
});

it('exibe o logo He4rt animado', function (): void {
    get('/redes')->assertSee('he4rt-logo', escape: false);
});

it('exibe o título e o acento de marca de cada link', function (): void {
    $html = get('/redes')->getContent();

    expect($html)->toContain('Escolha seu canal');
    expect(mb_substr_count($html, '--accent-light:'))->toBe(7);
    expect(mb_substr_count($html, '--accent-dark:'))->toBe(7);
    expect($html)->toContain('--accent-light: #0F172A; --accent-dark: #FFFFFF;')
        ->and($html)->toContain('--accent-light: #111827; --accent-dark: #FFFFFF;');
});

it('expõe o link de Redes sociais na navbar', function (): void {
    get('/redes')
        ->assertOk()
        ->assertSee('Redes sociais')
        ->assertSee('/redes', escape: false);
});

it('aplica a animação de entrada (logo desenha + conteúdo em cascata)', function (): void {
    get('/redes')
        ->assertSee('links-reveal', escape: false)
        ->assertSee('links-trace-draw', escape: false);
});

it('usa cores compatíveis com light mode na página de redes', function (): void {
    $html = get('/redes')->getContent();

    expect($html)->toContain('text-text-high')
        ->and($html)->toContain('flex items-center gap-2 text-text-high')
        ->and($html)->toContain('<span class="text-lg font-bold">He4rt Devs</span>')
        ->and($html)->toContain('background-color: color-mix(')
        ->and($html)->not->toContain('text-white/60')
        ->and($html)->not->toContain('text-white')
        ->and($html)->not->toContain('bg-white/5 px-6 py-3.5');
});
