<?php

declare(strict_types=1);

use He4rt\Contents\Articles\Models\Article as CatalogueArticle;
use He4rt\Contents\Models\ContentEntry;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

it('responde 200 na home', function (): void {
    get('/')->assertOk();
});

it('exibe o logo He4rt animado no hero', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('he4rt-logo', escape: false)
        ->assertSee('class="led', escape: false);
});

it('usa o layout do portal com suporte ao tema do sistema', function (): void {
    $html = get('/')->getContent();
    $htmlLang = str_replace('_', '-', app()->getLocale());

    // A tag agora é emitida pelo laravel/head (@head), sem a barra de fechamento.
    expect($html)->toContain('<meta name="color-scheme" content="light dark">')
        ->and($html)->not->toContain('<html lang="'.$htmlLang.'" class="dark">')
        ->and($html)->toContain('flex items-center gap-2 text-text-high')
        ->and($html)->toContain('<span class="text-lg font-bold">He4rt Devs</span>');
});

it('mostra os três artigos mais recentes do catálogo', function (): void {
    foreach (['Mais antigo', 'Do meio', 'Recente', 'O mais novo'] as $offset => $title) {
        ContentEntry::factory()->create([
            'contentable_type' => 'content_article',
            'contentable_id' => CatalogueArticle::factory()->create()->id,
            'title' => $title,
            'published_at' => now()->subDays(10 - $offset),
        ]);
    }

    get('/')
        ->assertOk()
        ->assertSee('O mais novo')
        ->assertSee('Recente')
        ->assertSee('Do meio')
        ->assertDontSee('Mais antigo');
});

it('omite a seção de artigos quando o catálogo está vazio', function (): void {
    get('/')
        ->assertOk()
        ->assertDontSee('O que a comunidade escreveu');
});

it('leva para o acervo pela navbar', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('href="/artigos"', escape: false);
});

it('leva para a galeria pela navbar', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('href="/galeria"', escape: false);
});
