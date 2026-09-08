<?php

declare(strict_types=1);

use He4rt\Marketing\ShortLink\Jobs\RecordShortLinkClick;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();

    // O cache positivo é eterno e o store `array` vive no processo: sem isto um
    // slug resolvido num teste vaza para o próximo.
    Cache::flush();

    Queue::fake();
});

function portalDeadShortLinkSlug(string $case): string
{
    return match ($case) {
        'inexistente' => 'nunca-existiu-z9x8w',
        'desativado' => ShortLink::factory()->disabled()->create()->slug,
        'vencido' => ShortLink::factory()->expired()->create()->slug,
        'soft-deletado' => tap(
            ShortLink::factory()->create(),
            fn (ShortLink $link) => $link->delete(),
        )->slug,
    };
}

it('redireciona com 302 e anexa o UTM configurado no link', function (): void {
    $link = ShortLink::factory()
        ->withUtm(['utm_source' => 'discord', 'utm_medium' => 'post'])
        ->create(['destination_url' => 'https://discord.gg/he4rt']);

    get('/l/'.$link->slug)
        ->assertStatus(302)
        ->assertHeader('Location', 'https://discord.gg/he4rt?utm_source=discord&utm_medium=post');
});

it('deixa o UTM que veio no clique ganhar do configurado no link', function (): void {
    $link = ShortLink::factory()
        ->withUtm(['utm_source' => 'discord'])
        ->create(['destination_url' => 'https://he4rt.dev/evento']);

    get('/l/'.$link->slug.'?utm_source=twitter')
        ->assertStatus(302)
        ->assertHeader('Location', 'https://he4rt.dev/evento?utm_source=twitter');
});

it('despacha o registro do clique no caminho feliz', function (): void {
    $link = ShortLink::factory()->create(['destination_url' => 'https://he4rt.dev']);

    get('/l/'.$link->slug)->assertStatus(302);

    Queue::assertPushed(RecordShortLinkClick::class, 1);
});

it('devolve 404 e não registra clique nenhum em desfecho morto', function (string $case): void {
    get('/l/'.portalDeadShortLinkSlug($case))->assertNotFound();

    Queue::assertNothingPushed();
})->with(['inexistente', 'desativado', 'vencido', 'soft-deletado']);

it('responde exatamente a mesma página nos quatro desfechos mortos', function (): void {
    $bodies = collect(['inexistente', 'desativado', 'vencido', 'soft-deletado'])
        ->map(fn (string $case): string => get('/l/'.portalDeadShortLinkSlug($case))
            ->assertNotFound()
            ->getContent())
        ->unique();

    expect($bodies)->toHaveCount(1);
});

it('mostra a página de marca com os dois CTAs quando o link não resolve', function (): void {
    get('/l/nunca-existiu-z9x8w')
        ->assertNotFound()
        ->assertSee('Esse link não está mais disponível')
        ->assertSee(route('home'), escape: false)
        ->assertSee(config()->string('he4rt.social_media.discord.url'), escape: false);
});

it('mantém a página de link morto fora do índice de busca', function (): void {
    get('/l/nunca-existiu-z9x8w')
        ->assertNotFound()
        ->assertSee('<meta name="robots" content="noindex, follow">', escape: false)
        ->assertSee('<title>Link indisponível - '.config()->string('app.name').'</title>', escape: false);
});

it('não casa slug com maiúscula, porque o canônico é minúsculo', function (): void {
    ShortLink::factory()->create(['slug' => 'discord-a3f9k', 'base_slug' => 'discord']);

    get('/l/Discord-A3F9K')->assertNotFound();

    Queue::assertNothingPushed();
});

it('passa a valer o destino novo já no clique seguinte à edição', function (): void {
    $link = ShortLink::factory()->create(['destination_url' => 'https://discord.gg/convite-antigo']);

    get('/l/'.$link->slug)->assertHeader('Location', 'https://discord.gg/convite-antigo');

    $link->update(['destination_url' => 'https://discord.gg/convite-novo']);

    get('/l/'.$link->slug)->assertHeader('Location', 'https://discord.gg/convite-novo');
});
