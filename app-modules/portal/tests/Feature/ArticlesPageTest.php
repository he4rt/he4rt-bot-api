<?php

declare(strict_types=1);

use He4rt\Contents\Articles\Models\Article as CatalogueArticle;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\User\Models\User;
use He4rt\Portal\Articles\ArticleFeed;
use He4rt\Portal\Articles\ArticlesPage;

use function Pest\Livewire\livewire;

/** @param array<string, mixed> $attributes */
function catalogueEntry(array $attributes = [], ?CatalogueArticle $detail = null): ContentEntry
{
    $detail ??= CatalogueArticle::factory()->create(['reading_time_minutes' => 4]);

    return ContentEntry::factory()->create([
        'contentable_type' => 'content_article',
        'contentable_id' => $detail->id,
        'author_handle' => 'alguem',
        'title' => 'Um artigo qualquer',
        'published_at' => now()->subMonth(),
        'reactions_count' => 10,
        'comments_count' => 2,
        ...$attributes,
    ]);
}

it('lista os artigos do catálogo com autores e temas', function (): void {
    catalogueEntry(['title' => 'Índices em produção', 'author_handle' => 'fernando']);
    catalogueEntry(['title' => 'Testes que importam', 'author_handle' => 'alicia', 'tags' => ['testing']]);

    livewire(ArticlesPage::class)
        ->assertOk()
        ->assertSee('Índices em produção')
        ->assertSee('Testes que importam')
        ->assertSee('fernando')
        ->assertSee('alicia')
        ->assertSee('#testing');
});

it('credita o nome do usuário quando a identidade está vinculada', function (): void {
    $user = User::factory()->create(['name' => 'Cherry Ramatis', 'username' => 'cherry']);

    catalogueEntry(['author_handle' => 'cherryramatis', 'author_id' => $user->id]);

    $article = resolve(ArticleFeed::class)->articles()[0];

    expect($article->authorName)->toBe('Cherry Ramatis')
        ->and($article->authorUsername)->toBe('cherryramatis')
        ->and($article->authorAvatar)->toBe('https://github.com/cherry.png');
});

it('assina com o handle da fonte quando ninguém vinculou a identidade', function (): void {
    catalogueEntry(['author_handle' => 'anonimo', 'author_id' => null]);

    $article = resolve(ArticleFeed::class)->articles()[0];

    expect($article->authorName)->toBe('anonimo')
        ->and($article->authorAvatar)->toBeEmpty();
});

it('ordena do mais recente para o mais antigo', function (): void {
    catalogueEntry(['title' => 'Antigo', 'published_at' => now()->subYears(2)]);
    catalogueEntry(['title' => 'Recente', 'published_at' => now()->subDay()]);

    $titles = array_map(fn (He4rt\Portal\Articles\DTOs\Article $article): string => $article->title, resolve(ArticleFeed::class)->articles());

    expect($titles)->toBe(['Recente', 'Antigo']);
});

it('elege como destaque o mais reagido dos últimos 12 meses, não o campeão histórico', function (): void {
    catalogueEntry(['title' => 'Campeão histórico', 'reactions_count' => 565, 'published_at' => now()->subYears(3)]);
    catalogueEntry(['title' => 'Destaque recente', 'reactions_count' => 215, 'published_at' => now()->subMonths(2)]);

    expect(resolve(ArticleFeed::class)->highlight()?->title)->toBe('Destaque recente');
});

it('agrega volume e alcance por pessoa', function (): void {
    catalogueEntry(['reactions_count' => 100]);
    catalogueEntry(['reactions_count' => 40]);

    $authors = resolve(ArticleFeed::class)->authors();

    expect($authors)->toHaveCount(1)
        ->and($authors[0]->articleCount)->toBe(2)
        ->and($authors[0]->reactions)->toBe(140);
});

it('trata métrica não medida como zero', function (): void {
    catalogueEntry(['reactions_count' => null, 'comments_count' => null]);

    $article = resolve(ArticleFeed::class)->articles()[0];

    expect($article->reactions)->toBe(0)
        ->and($article->comments)->toBe(0);
});

it('preserva a capa nula para a view cair no fallback', function (): void {
    catalogueEntry(['thumbnail_url' => null]);

    expect(resolve(ArticleFeed::class)->articles()[0]->coverImage)->toBeNull();
});

it('sobrevive a um artigo ainda sem detalhe hidratado', function (): void {
    $entry = catalogueEntry();
    $entry->contentable?->update(['description' => null, 'reading_time_minutes' => null]);

    $article = resolve(ArticleFeed::class)->articles()[0];

    expect($article->description)->toBeEmpty()
        ->and($article->readingMinutes)->toBe(0);
});

it('mede a fatia do acervo que cada tema carrega', function (): void {
    catalogueEntry(['tags' => ['braziliandevs', 'career']]);
    catalogueEntry(['tags' => ['braziliandevs']]);

    $feed = resolve(ArticleFeed::class);
    $topics = $feed->topics();

    expect($topics[0]->tag)->toBe('braziliandevs')
        ->and($topics[0]->share($feed->stats()['articles']))->toBe(1.0);
});

it('responde na rota /artigos', function (): void {
    catalogueEntry();

    $this->get('/artigos')
        ->assertOk()
        ->assertSee('Learn in public', escape: false);
});

it('diz que o acervo está vazio em vez de mostrar uma grade em branco', function (): void {
    $this->get('/artigos')
        ->assertOk()
        ->assertSee('Ainda não há artigos por aqui.');
});
