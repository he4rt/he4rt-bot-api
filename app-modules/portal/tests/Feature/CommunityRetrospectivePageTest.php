<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Congela o snapshot do período fixo (junho/2026) a partir das fontes vivas e cria
 * uma edição publicada com ele. Como usa as fontes reais, os props dos slides
 * batem com os partials — exercita todo o caminho congelar -> compor -> renderizar.
 */
function publishRetrospective(array $overrides = []): Retrospective
{
    $since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $until = CarbonImmutable::parse('2026-06-30 23:59:59');

    $snapshot = resolve(CompileSnapshot::class)->execute(
        Period::of($since, $until),
        new SourceFilters(),
    );

    return Retrospective::factory()->published($snapshot)->create(array_merge([
        'since' => $since,
        'until' => $until,
    ], $overrides));
}

it('abre como onboarding quando a edição pede, com edição e apresentadores', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);
    $hosts = collect(['hostdev' => 'Daniel Reis', 'cohost' => 'Maria Silva'])
        ->map(function (string $name, string $username): User {
            $user = User::factory()->create(['name' => $name, 'username' => $username]);
            ExternalIdentity::factory()->create([
                'model_id' => $user->id,
                'provider' => IdentityProvider::GitHub,
                'metadata' => ['username' => $username],
            ]);

            return $user;
        });

    // Uma edição de onboarding anterior: a publicada vira a 2ª.
    Retrospective::factory()->onboarding()->create([
        'since' => CarbonImmutable::parse('2026-05-01'),
        'until' => CarbonImmutable::parse('2026-05-31'),
    ]);

    publishRetrospective([
        'cover_kind' => CoverKind::Onboarding,
        'deck_config' => new DeckConfig()->withHosts([$hosts['hostdev']->id, $hosts['cohost']->id]),
        'cover_title' => null,
    ]);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('data-label="Boas-vindas"', escape: false)
        ->assertSee('2ª EDIÇÃO')
        ->assertSee('Bem-vindo à He4rt')
        ->assertSee('Quem apresenta hoje')
        ->assertSeeInOrder(['Daniel Reis', '@hostdev', 'Maria Silva', '@cohost'])
        ->assertSee('https://github.com/hostdev.png')
        ->assertDontSee('data-label="Abertura"', escape: false);
});

it('mantém a capa de retrospectiva por padrão, sem apresentador', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);
    publishRetrospective(['cover_title' => null]);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('data-label="Abertura"', escape: false)
        ->assertSee('Quem fez a He4rt')
        ->assertDontSee('Quem apresenta')
        ->assertDontSee('EDIÇÃO');
});

it('mostra o estado vazio quando não há edição publicada', function (): void {
    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('Métricas')
        ->assertSee('reunião')
        ->assertSee('discord.gg/he4rt')
        ->assertDontSee('Recorte');
});

it('renderiza o deck da edição publicada a partir do snapshot congelado', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    publishRetrospective(['cover_title' => 'Retro de Junho', 'closing_text' => 'Valeu, pessoal!']);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('GitHub')
        ->assertSee('maria')
        ->assertSee('Retro de Junho')
        ->assertSee('Valeu, pessoal!')
        ->assertDontSee('Recorte');
});

it('responde na rota pública /comunidade/retrospectiva', function (): void {
    test()->get('/comunidade/retrospectiva')->assertOk();
});

it('não vaza rascunho na página pública', function (): void {
    Retrospective::factory()->create(['cover_title' => 'Rascunho Secreto']);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertDontSee('Rascunho Secreto')
        ->assertSee('reunião');
});

it('respeita o on/off de fonte do deck_config na edição publicada', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    publishRetrospective([
        'cover_title' => 'Sem GitHub',
        'deck_config' => new DeckConfig(hiddenSources: ['github']),
    ]);

    // github era a única fonte com dado; oculta => deck fica sem fontes => estado vazio.
    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertDontSee('maria');
});

it('preview autenticado monta o rascunho coletado ao vivo', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    $retrospective = Retrospective::factory()->create([
        'since' => CarbonImmutable::parse('2026-06-01 00:00:00'),
        'until' => CarbonImmutable::parse('2026-06-30 23:59:59'),
    ]);

    test()->actingAs(User::factory()->create())
        ->get(route('community.retrospective.preview', $retrospective))
        ->assertOk()
        ->assertSee('maria');
});

it('preview nega acesso a visitante não autenticado', function (): void {
    $retrospective = Retrospective::factory()->create();

    test()->get(route('community.retrospective.preview', $retrospective))
        ->assertForbidden();
});

it('apresenta a He4rt entre a capa e os números', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    publishRetrospective();

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        // Linha do tempo: os marcos e a onda gerada a partir deles.
        ->assertSee('A He4rt bate desde 2018')
        ->assertSee('4noobs')
        ->assertSee('class="tl-ecg"', escape: false)
        // Eventos presenciais: fotos fixas num carrossel, logo depois do manifesto.
        ->assertSee('Não somos só uma comunidade online')
        ->assertSee('images/retro/events/')
        ->assertSeeInOrder(['A He4rt bate desde 2018', 'Não somos só uma comunidade online', 'Reunião Semanal'])
        // Iniciativas, com a órbita ligada à lista pela cor.
        ->assertSee('Reunião Semanal')
        ->assertSee('Spaces')
        ->assertSee('frequência de encontro')
        // Canais: constelação com todos em pé de igualdade — vêm do config,
        // e nenhum leva selo nem CTA próprio.
        ->assertSee('Todos os caminhos dão no mesmo lugar')
        ->assertSee('github.com')
        ->assertSee('class="const-star"', escape: false)
        ->assertDontSee('medido aqui');
});

it('não anuncia marco que ainda não tinha acontecido no recorte', function (): void {
    // Um recorte de 2021 retrata uma He4rt de 2021: o meetup de 2022 e o
    // LaravelDaySP de 2026 não podem aparecer numa retrospectiva daquele ano.
    $since = CarbonImmutable::parse('2021-01-01 00:00:00');
    $until = CarbonImmutable::parse('2021-12-31 23:59:59');

    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2021-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    $snapshot = resolve(CompileSnapshot::class)->execute(
        Period::of($since, $until),
        new SourceFilters(),
    );

    Retrospective::factory()->published($snapshot)->create([
        'since' => $since,
        'until' => $until,
    ]);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('He4rt Conf')
        ->assertDontSee('Primeiro meetup presencial')
        ->assertDontSee('LaravelDaySP')
        // O slide de eventos segue a mesma régua: sem foto de evento futuro.
        ->assertDontSee('images/retro/events/')
        ->assertSee('ainda era só online');
});
