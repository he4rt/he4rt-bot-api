<?php

declare(strict_types=1);

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * Regressão do bug que originou estes testes: sem og:image, o Google escolhia
 * sozinho uma <img> da página e acabou indexando o avatar do GitHub de um
 * membro da comunidade como thumbnail do site na busca.
 */
it('declara uma og:image própria, absoluta e dimensionada na home', function (): void {
    $html = get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<meta property="og:image" content="'.asset('images/og-default.png').'">')
        ->toContain('<meta property="og:image:width" content="1200">')
        ->toContain('<meta property="og:image:height" content="630">')
        ->toContain('<meta property="og:image:type" content="image/png">')
        ->toContain('<meta property="og:image:alt"');
});

it('autoriza preview de imagem grande para o card de resultado', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">', escape: false);
});

it('serve o arquivo de og:image em 1200x630', function (): void {
    $path = public_path('images/og-default.png');

    expect($path)->toBeReadableFile();

    [$width, $height] = getimagesize($path);

    expect($width)->toBe(1_200)
        ->and($height)->toBe(630);
});

it('renderiza o card do X/Twitter a partir do mesmo título, descrição e imagem', function (): void {
    $html = get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->toContain('<meta name="twitter:site" content="@He4rtDevs">')
        ->toContain('<meta name="twitter:image" content="'.asset('images/og-default.png').'">');
});

it('emite description e canonical em toda página do portal', function (string $uri): void {
    $html = get($uri)->assertOk()->getContent();

    expect($html)
        ->toContain('<meta name="description" content="')
        ->toContain('<link rel="canonical" href="');
})->with([
    'home' => '/',
    'redes' => '/redes',
    'retrospectiva' => '/comunidade/retrospectiva',
]);

it('usa o título exato da home, sem repetir a marca no sufixo', function (): void {
    $html = get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>He4rt Developers — Comunidade de desenvolvedores</title>')
        ->not->toContain('Home - ');
});

it('aplica o sufixo da marca nas demais páginas', function (): void {
    get('/redes')
        ->assertOk()
        ->assertSee('<title>Nossas redes - '.config('app.name').'</title>', escape: false);
});

it('descreve a página de redes com texto próprio, não o default do site', function (): void {
    $html = get('/redes')->assertOk()->getContent();

    expect($html)
        ->toContain('<meta name="description" content="Todos os canais oficiais da He4rt Developers')
        // O default só sobrevive no JSON-LD da Organization, nunca na meta description da página.
        ->not->toContain('<meta name="description" content="'.config()->string('he4rt.seo.description').'"');
});

/**
 * A retrospectiva guarda os filtros na query string (#[Url]); sem um canonical
 * fixo, cada combinação viraria uma URL indexável distinta com o mesmo conteúdo.
 */
it('consolida os filtros da retrospectiva em um canonical único', function (): void {
    $html = get('/comunidade/retrospectiva?sort=total&hideBots=1&byRepo=1')
        ->assertOk()
        ->getContent();

    // O laravel/head normaliza o canonical para https por padrão.
    expect($html)->toContain('<link rel="canonical" href="'.secure_url('/comunidade/retrospectiva').'">');
});

it('publica o JSON-LD de Organization com logo e perfis sociais', function (): void {
    $html = get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('"@type":"Organization"')
        ->toContain('"logo":"'.asset('images/logo.png').'"')
        ->toContain('"sameAs"')
        ->toContain('https://github.com/he4rt');
});

it('publica o JSON-LD de WebSite em pt-BR', function (): void {
    $html = get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('"@type":"WebSite"')
        ->toContain('"inLanguage":"pt-BR"');
});

it('serve um sitemap XML com as páginas públicas do portal', function (): void {
    $response = get('/sitemap.xml')->assertOk();

    $response->assertHeader('Content-Type', 'application/xml');

    expect($response->getContent())
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<loc>'.route('home').'</loc>')
        ->toContain('<loc>'.route('social-links').'</loc>')
        ->toContain('<loc>'.route('gallery').'</loc>')
        ->toContain('<loc>'.route('community.retrospective').'</loc>');
});

it('mantém painéis e rotas de infraestrutura fora do robots.txt', function (): void {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /app')
        ->toContain('Sitemap: https://heartdevs.com/sitemap.xml');
});
