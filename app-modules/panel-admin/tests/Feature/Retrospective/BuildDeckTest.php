<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\DTOs\PromotionMetricGroup;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Community\Retrospective\Enums\PromotionStage;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Jobs\CompileRetrospectiveSnapshot;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\BuildDeck;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\RetrospectiveResource;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\PromotablePeople;
use He4rt\Portal\Retrospective\AboutSection;
use He4rt\Portal\Retrospective\DeckPresentation;
use He4rt\Portal\Retrospective\PromotionSection;
use Illuminate\Support\Facades\Bus;
use Tests\Support\Retrospective\PlainRetrospectiveSource;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * Retrospectiva com ordem editorial explícita: a ordem de descoberta das fontes
 * depende do boot dos providers, então os testes de reordenação fixam o ponto
 * de partida em vez de assumi-lo.
 */
function retrospectiveWithOrder(array $order = ['github', 'discord'], array $exclusions = []): Retrospective
{
    return Retrospective::factory()->create([
        'deck_config' => new DeckConfig(order: $order, exclusions: $exclusions),
    ]);
}

/**
 * Edição publicada com snapshot real do GitHub congelado dentro. Os testes de
 * preview precisam de slides de verdade: é a lista composta que define em que
 * índice cada slide cai no deck.
 */
function publishedRetrospectiveWithGithub(): Retrospective
{
    $since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $until = CarbonImmutable::parse('2026-06-30 23:59:59');

    GithubContribution::factory()->create([
        'actor_login' => 'maria',
        'external_ref' => 'pr:1',
        'occurred_at' => '2026-06-02',
        'metadata' => ['title' => 'Um PR', 'state' => 'open', 'merged' => false, 'additions' => 10],
    ]);

    $snapshot = resolve(CompileSnapshot::class)->execute(Period::of($since, $until), new SourceFilters());

    return Retrospective::factory()->published($snapshot)->create([
        'since' => $since,
        'until' => $until,
        'cover_title' => 'Retro de Junho',
        'deck_config' => new DeckConfig(order: ['github', 'discord']),
    ]);
}

/**
 * Um PR dentro do recorte da edição, para a varredura de exclusionCandidates()
 * do GithubSource ter o que oferecer no picker.
 */
function contributionWithin(Retrospective $retrospective, string $ref): GithubContribution
{
    return GithubContribution::factory()->create([
        'external_ref' => $ref,
        'occurred_at' => $retrospective->since->copy()->addDay(),
        'metadata' => ['title' => 'Um PR grande', 'additions' => 500, 'deletions' => 10],
    ]);
}

test('o builder atende na chave edit com a rota /deck', function (): void {
    $retrospective = Retrospective::factory()->create();

    $url = RetrospectiveResource::getUrl('edit', ['record' => $retrospective]);

    expect($url)->toEndWith('/deck');

    test()->get($url)->assertOk();
});

test('abre com a timeline das fontes e o deck embutido no preview', function (): void {
    $retrospective = retrospectiveWithOrder();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->assertSee('GitHub')
        ->assertSee('Discord')
        ->assertSee('Repositórios')
        // O deck vive no mesmo DOM do builder, não num iframe.
        ->assertSee('retro-embed')
        ->assertDontSee('<iframe', escape: false)
        ->assertSee(route('community.retrospective.preview', $retrospective), escape: false);
});

test('desliga e religa uma fonte pelo inspector', function (): void {
    $retrospective = retrospectiveWithOrder();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:discord')
        ->assertFormSet(['visible' => true])
        ->fillForm(['visible' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($retrospective->fresh()->deck_config->showsSource('discord'))->toBeFalse();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:discord')
        ->assertFormSet(['visible' => false])
        ->fillForm(['visible' => true])
        ->call('save');

    expect($retrospective->fresh()->deck_config->showsSource('discord'))->toBeTrue();
});

test('desliga um kind de slide sem tocar os outros kinds da fonte', function (): void {
    $retrospective = retrospectiveWithOrder();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'slide:github.repos')
        ->fillForm(['visible' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $config = $retrospective->fresh()->deck_config;

    expect($config->hiddenSlides)->toBe(['github.repos'])
        ->and($config->showsSlide('github.panorama'))->toBeTrue();
});

test('sobe e desce um bloco na ordem editorial', function (): void {
    $retrospective = retrospectiveWithOrder(['github', 'discord']);

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('moveSource', 'discord', -1);

    expect($retrospective->fresh()->deck_config->order)->toBe(['discord', 'github']);

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('moveSource', 'discord', 1);

    expect($retrospective->fresh()->deck_config->order)->toBe(['github', 'discord']);
});

test('reordenar não mexe em on/off nem em exclusions', function (): void {
    $retrospective = Retrospective::factory()->create([
        'deck_config' => new DeckConfig(
            order: ['github', 'discord'],
            hiddenSources: ['discord'],
            hiddenSlides: ['github.repos'],
            exclusions: ['github' => ['pr:1']],
        ),
    ]);

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('moveSource', 'discord', -1);

    $config = $retrospective->fresh()->deck_config;

    expect($config->order)->toBe(['discord', 'github'])
        ->and($config->hiddenSources)->toBe(['discord'])
        ->and($config->hiddenSlides)->toBe(['github.repos'])
        ->and($config->exclusionsFor('github'))->toBe(['pr:1']);
});

test('o picker oferece os candidatos que a fonte varreu no recorte', function (): void {
    $retrospective = retrospectiveWithOrder();
    $contribution = contributionWithin($retrospective, 'pr:4242');

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:github')
        ->assertSee('Um PR grande')
        ->assertSee($contribution->actor_login);
});

test('salva no deck_config as exclusions escolhidas no picker', function (): void {
    $retrospective = retrospectiveWithOrder();
    contributionWithin($retrospective, 'pr:4242');

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:github')
        ->fillForm(['exclusion_items' => ['pr:4242']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($retrospective->fresh()->deck_config->exclusionsFor('github'))->toBe(['pr:4242']);
});

test('preserva refs já excluídos que ficaram fora do teto do picker', function (): void {
    $retrospective = retrospectiveWithOrder(exclusions: ['github' => ['pr:999999', 'pr:4242']]);
    contributionWithin($retrospective, 'pr:4242');

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:github')
        ->assertFormSet(['exclusion_items' => ['pr:4242']])
        ->fillForm(['exclusion_items' => []])
        ->call('save');

    expect($retrospective->fresh()->deck_config->exclusionsFor('github'))->toBe(['pr:999999']);
});

test('avisa que exclusion exige republicar, e só quando ela muda', function (): void {
    $retrospective = retrospectiveWithOrder();
    contributionWithin($retrospective, 'pr:4242');

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:github')
        ->fillForm(['exclusion_items' => ['pr:4242']])
        ->call('save')
        ->assertNotified('Exclusion alterada');

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:github')
        ->fillForm(['visible' => false])
        ->call('save')
        ->assertNotNotified('Exclusion alterada');
});

test('a fonte que não cura entra na timeline com on/off, mas sem picker', function (): void {
    PlainRetrospectiveSource::register();

    $retrospective = retrospectiveWithOrder();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->assertSee(PlainRetrospectiveSource::LABEL)
        ->call('select', 'source:'.PlainRetrospectiveSource::KEY)
        ->assertFormFieldExists('visible')
        ->assertFormFieldDoesNotExist('exclusion_items')
        ->fillForm(['visible' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($retrospective->fresh()->deck_config->showsSource(PlainRetrospectiveSource::KEY))->toBeFalse();
});

test('salva capa e período nas colunas da edição', function (): void {
    $retrospective = Retrospective::factory()->create(['hide_bots' => true]);

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'cover')
        ->fillForm([
            'title' => 'Retro de Julho',
            'cover_title' => 'Julho foi grande',
            'cover_intro' => 'Uma introdução.',
            'hide_bots' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $retrospective->fresh();

    expect($fresh->title)->toBe('Retro de Julho')
        ->and($fresh->cover_title)->toBe('Julho foi grande')
        ->and($fresh->cover_intro)->toBe('Uma introdução.')
        ->and($fresh->hide_bots)->toBeFalse();
});

test('salva a capa de onboarding com apresentadores em ordem e limpa a lista ao voltar', function (): void {
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
    $retrospective = Retrospective::factory()->create();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'cover')
        ->fillForm([
            'cover_kind' => CoverKind::Onboarding->value,
            'hosts' => [$hosts['cohost']->id, $hosts['hostdev']->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $retrospective->fresh();
    $deck = $page->instance()->deck();

    expect($fresh->cover_kind)->toBe(CoverKind::Onboarding)
        ->and($fresh->deck_config->hosts)->toBe([$hosts['cohost']->id, $hosts['hostdev']->id])
        ->and($page->instance()->viewPath())
        ->toBe('app-modules/portal/resources/views/components/retro/slides/cover/onboarding.blade.php')
        ->and(array_map(fn ($host): string => $host->name, $deck['hosts']))->toBe(['Maria Silva', 'Daniel Reis'])
        ->and($deck['edition'])->toBe(1);

    $page
        ->fillForm(['cover_kind' => CoverKind::Retrospective->value])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $retrospective->fresh();

    expect($fresh->cover_kind)->toBe(CoverKind::Retrospective)
        ->and($fresh->deck_config->hosts)->toBeEmpty();
});

test('a capa exige título', function (): void {
    $retrospective = Retrospective::factory()->create();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'cover')
        ->fillForm(['title' => ''])
        ->call('save')
        ->assertHasFormErrors(['title' => 'required']);
});

test('salva a mensagem de fecho', function (): void {
    $retrospective = Retrospective::factory()->create();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'closing')
        ->fillForm(['closing_text' => 'Até a próxima.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($retrospective->fresh()->closing_text)->toBe('Até a próxima.');
});

test('publicar pelo builder marca publicando e enfileira o job', function (): void {
    Bus::fake();

    $retrospective = Retrospective::factory()->create();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->callAction('publish')
        ->assertNotified();

    expect($retrospective->fresh()->status)->toBe(RetrospectiveStatus::Publishing);

    Bus::assertDispatched(fn (CompileRetrospectiveSnapshot $job): bool => $job->retrospective->is($retrospective));
});

test('apagar pelo builder volta para a lista', function (): void {
    $retrospective = Retrospective::factory()->create();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->callAction('delete')
        ->assertRedirect(RetrospectiveResource::getUrl('index'));

    expect(Retrospective::query()->whereKey($retrospective->id)->exists())->toBeFalse();
});

test('salvar recria o deck em vez de morfá-lo, para o Alpine reler os slides', function (): void {
    $retrospective = Retrospective::factory()->create();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id]);

    $before = $component->instance()->deck()['stateKey'];

    $component
        ->call('select', 'closing')
        ->fillForm(['closing_text' => 'Novo fecho.'])
        ->call('save');

    expect($component->instance()->deck()['stateKey'])->not->toBe($before);
});

test('o deck embutido usa o mesmo caminho de render da página pública', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $fromBuilder = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->instance()
        ->deck();

    $fromPortal = DeckPresentation::for($retrospective);

    expect(array_map(fn ($source): string => $source->key, $fromBuilder['sources']))
        ->toBe(array_map(fn (SourceResult $source): string => $source->key, $fromPortal['sources']))
        ->and($fromBuilder['coverTitle'])->toBe($fromPortal['coverTitle']);
});

test('a seleção leva o preview até o slide correspondente', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id]);

    // A capa é sempre o slide 0.
    expect($component->instance()->previewIndex())->toBe(0);

    $kinds = array_map(
        fn ($slide): string => $slide->kind(),
        $component->instance()->deck()['sources'][0]->slides,
    );

    // O primeiro slide composto não é o 1: a capa e a seção fixa sobre a He4rt
    // vêm antes. O deslocamento sai do builder, não de um número escrito aqui —
    // acrescentar um slide à seção não pode obrigar a corrigir o teste.
    $offset = $component->instance()->composedOffset();

    $component->call('select', 'slide:'.$kinds[0]);

    expect($component->instance()->previewIndex())->toBe($offset);

    // O fecho fecha o deck, depois de tudo.
    $component->call('select', 'closing');

    expect($component->instance()->previewIndex())->toBe(count($kinds) + $offset);
});

test('a seção fixa sobre a He4rt ocupa os slides logo depois da capa', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id]);

    foreach (AboutSection::slides() as $position => $slide) {
        $component->call('select', InspectorMode::About->value.':'.$slide->key);

        expect($component->instance()->previewIndex())->toBe($position + 1);
    }

    // Sem campo para salvar, mas com o arquivo à mão: é assim que se mexe nela.
    expect($component->instance()->viewPath())
        ->toContain('app-modules/portal/resources/views/components/retro/slides/about/');
});

test('selecionar um slide desligado cai na capa em vez de apontar para o nada', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'slide:github.panorama')
        ->fillForm(['visible' => false])
        ->call('save');

    expect($component->instance()->previewIndex())->toBe(0);
});

test('o builder mostra o status da edição', function (): void {
    $retrospective = Retrospective::factory()->create();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->assertSee(RetrospectiveStatus::Draft->getLabel());
});

test('o builder avisa quando a edição publicada está com exclusion alterada depois', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(
            sources: [],
            filters: new SourceFilters(hideBots: true, exclusions: ['pr:1']),
        ))
        ->create([
            'hide_bots' => true,
            'deck_config' => new DeckConfig(exclusions: ['github' => ['pr:1', 'pr:2']]),
        ]);

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->assertSee('Republique');
});

test('o builder não avisa drift quando só ordem e on/off mudaram', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(sources: [], filters: new SourceFilters(hideBots: true)))
        ->create([
            'hide_bots' => true,
            'deck_config' => new DeckConfig(order: ['discord', 'github'], hiddenSources: ['discord']),
        ]);

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->assertDontSee('Republique');
});

test('o builder acompanha a transição de publicando para publicada', function (): void {
    $retrospective = Retrospective::factory()->create(['status' => RetrospectiveStatus::Publishing]);

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->assertSee(RetrospectiveStatus::Publishing->getLabel());

    // O job termina em segundo plano; sem poll o operador ficaria olhando
    // "Publicando" até recarregar na mão.
    $retrospective->update([
        'status' => RetrospectiveStatus::Published,
        'published_at' => now(),
        'snapshot' => new RetrospectiveSnapshot(),
    ]);

    $component
        ->call('refreshStatus')
        ->assertSee(RetrospectiveStatus::Published->getLabel())
        ->assertDontSee(RetrospectiveStatus::Publishing->getLabel());
});

test('o builder ocupa a largura inteira da viewport', function (): void {
    // Três colunas com um deck inteiro no meio não cabem no 7xl padrão do painel.
    $retrospective = Retrospective::factory()->create();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->id])->instance();

    expect($page->getMaxContentWidth())->toBe(Width::Full);
});

test('o inspector não repete um cabeçalho genérico acima da seção do formulário', function (): void {
    // A Section do formulário já nomeia o alvo ("Bloco: Discord") e explica o efeito;
    // um cabeçalho com o label do modo em cima dizia a mesma coisa, mais vago.
    $retrospective = retrospectiveWithOrder();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:discord')
        ->assertSee('Bloco: Discord')
        ->assertDontSee(InspectorMode::Source->getLabel())
        ->assertDontSee(InspectorMode::Source->getDescription());
});

test('o cabeçalho do preview mostra o arquivo da view do slide selecionado', function (): void {
    $retrospective = retrospectiveWithOrder();

    livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'slide:discord.new_members')
        ->assertSee('app-modules/portal/resources/views/retro/slides/discord/new-members.blade.php');
});

test('o arquivo acompanha a troca de slide', function (): void {
    $retrospective = retrospectiveWithOrder();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'slide:github.repos');

    expect($page->instance()->viewPath())
        ->toBe('app-modules/portal/resources/views/retro/slides/github/repos.blade.php');

    $page->call('select', InspectorMode::Cover->value);

    expect($page->instance()->viewPath())
        ->toBe('app-modules/portal/resources/views/components/retro/slides/cover/retrospective.blade.php');
});

test('bloco de fonte não anuncia arquivo: quem tem view é o slide', function (): void {
    $retrospective = retrospectiveWithOrder();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'source:github');

    expect($page->instance()->viewPath())->toBeNull();
});

test('kind sem partial não inventa caminho', function (): void {
    $retrospective = retrospectiveWithOrder();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('select', 'slide:github.kind-que-nao-existe');

    expect($page->instance()->viewPath())->toBeNull();
});

test('navegar dentro do deck move a seleção da estrutura junto', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id]);

    $kinds = array_map(
        fn (Slide $slide): string => $slide->kind(),
        $component->instance()->deck()['sources'][0]->slides,
    );

    $offset = $component->instance()->composedOffset();

    // O slide logo depois da capa é a seção fixa, não o primeiro slide de fonte.
    $component->call('selectByIndex', 1);

    expect($component->instance()->selection()->token())
        ->toBe(InspectorMode::About->value.':'.AboutSection::slides()[0]->key);

    // O deck avisa que parou no primeiro slide composto.
    $component->call('selectByIndex', $offset);

    expect($component->instance()->selection()->token())->toBe('slide:'.$kinds[0]);

    // Voltar para a capa.
    $component->call('selectByIndex', 0);

    expect($component->instance()->selection()->token())->toBe(InspectorMode::Cover->value);

    // Passar do último slide composto é o fecho.
    $component->call('selectByIndex', count($kinds) + $offset);

    expect($component->instance()->selection()->token())->toBe(InspectorMode::Closing->value);
});

test('o inspector acompanha o slide para onde o deck foi', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id]);

    $component
        ->call('selectByIndex', $component->instance()->composedOffset())
        // O inspector troca de modo: a capa edita título e período, o slide edita on/off.
        ->assertSee('Exibir no deck')
        ->assertSee('app-modules/portal/resources/views/retro/slides/');
});

test('ir e voltar entre estrutura e deck estabiliza no mesmo slide', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id]);

    $kind = $component->instance()->deck()['sources'][0]->slides[0]->kind();

    // Estrutura -> deck: a seleção manda o preview para o índice 1.
    $component->call('select', 'slide:'.$kind);

    $index = $component->instance()->previewIndex();

    // Deck -> estrutura: o mesmo índice de volta não muda mais nada. É o que impede
    // as duas pontas de ficarem se empurrando.
    $component->call('selectByIndex', $index);

    expect($component->instance()->selection()->token())->toBe('slide:'.$kind)
        ->and($component->instance()->previewIndex())->toBe($index);
});

test('índice fora do deck cai na capa em vez de explodir', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $component = livewire(BuildDeck::class, ['record' => $retrospective->id])
        ->call('selectByIndex', -3);

    expect($component->instance()->selection()->token())->toBe(InspectorMode::Cover->value);
});

test('nenhuma ação Livewire nasce de dentro dos islands do builder', function (): void {
    // Um wire:click dentro de um island vira chamada ESCOPADA ao island: o
    // inspector não atualiza no mesmo roundtrip e cada clique paga o re-render
    // da tira inteira. Os botões despacham filmstrip-call e o listener mora
    // fora do island — este teste impede o wire:click de voltar.
    $views = [
        base_path('app-modules/panel-admin/resources/views/components/retrospective/filmstrip-thumb.blade.php'),
        base_path('app-modules/panel-admin/resources/views/components/retrospective/filmstrip-group.blade.php'),
    ];

    foreach ($views as $view) {
        expect(file_get_contents($view))->not->toContain('wire:click', basename($view));
    }

    $builder = file_get_contents(base_path('app-modules/panel-admin/resources/views/retrospective/build-deck.blade.php'));

    preg_match_all('/@island\(.*?\).*?@endisland/s', $builder, $islands);

    expect($islands[0])->not->toBeEmpty()->each->not->toContain('wire:click');
});

/**
 * Snapshot com o ritual da tag dentro, já medido. Monta os cartões à mão em vez
 * de passar pelo ComposePromotions: o que estes testes verificam é o
 * POSICIONAMENTO e a curadoria, não a medição — que tem teste próprio no
 * community.
 */
function publishedRetrospectiveWithPromotions(): Retrospective
{
    $since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $until = CarbonImmutable::parse('2026-06-30 23:59:59');

    // Com uma fonte de verdade dentro: sem nenhuma o portal desenha só o slide
    // "sem dado", e um deck vazio não teria índice para o ritual ocupar.
    GithubContribution::factory()->create([
        'actor_login' => 'maria',
        'external_ref' => 'pr:1',
        'occurred_at' => '2026-06-02',
        'metadata' => ['title' => 'Um PR', 'state' => 'open', 'merged' => false, 'additions' => 10],
    ]);

    $people = User::factory()->count(3)->create();

    $card = fn (string $id, PromotionStage $stage): PromotionCard => new PromotionCard(
        userId: $id,
        name: 'Fulana '.$id,
        username: 'fulana'.$id,
        avatar: 'https://example.test/'.$id.'.png',
        stage: $stage,
        reason: 'segurou o #ajuda',
        groups: [new PromotionMetricGroup('discord', 'Discord', [new Metric('Mensagens', 8_132)])],
    );

    $collected = resolve(CompileSnapshot::class)->execute(Period::of($since, $until), new SourceFilters());

    $snapshot = new RetrospectiveSnapshot(
        sources: $collected->sources,
        filters: new SourceFilters(),
        promotions: [
            $card($people[0]->id, PromotionStage::Spotlight),
            $card($people[1]->id, PromotionStage::Promoted),
            $card($people[2]->id, PromotionStage::Promoted),
        ],
    );

    return Retrospective::factory()->published($snapshot)->create([
        'since' => $since,
        'until' => $until,
        'deck_config' => new DeckConfig(
            order: ['github', 'discord'],
            promotions: [
                new PromotionEntry($people[0]->id, PromotionStage::Spotlight, 'segurou o #ajuda'),
                new PromotionEntry($people[1]->id, PromotionStage::Promoted, 'segurou o #ajuda'),
                new PromotionEntry($people[2]->id, PromotionStage::Promoted, 'segurou o #ajuda'),
            ],
        ),
    ]);
}

it('seleciona um slide do ritual pela tira, mesmo sem ninguém escolhido ainda', function (): void {
    $retrospective = retrospectiveWithOrder();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->getKey()])
        ->call('select', InspectorMode::Promotion->value.':'.PromotionSection::SPOTLIGHT)
        ->assertSet('selection', 'promotion:'.PromotionSection::SPOTLIGHT)
        ->assertHasNoErrors();

    // Sem gente escolhida o slide não existe no deck: não há para onde levar o
    // preview, e é isso que impede a tira de rolar de volta para a capa quando o
    // operador clica na miniatura vazia para preenchê-la.
    expect($page->instance()->previewTarget())->toBeNull()
        ->and($page->instance()->selectionLabel())->toContain('Destaques');
});

it('não mexe no preview ao selecionar um slide que o deck não desenha', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->getKey()]);

    // Kind existente no catálogo do GitHub, mas sem dado neste recorte.
    $page->call('select', InspectorMode::Slide->value.':discord.voice_board');

    expect($page->instance()->previewTarget())->toBeNull();

    $page->assertNotDispatched('retro-goto');
});

it('escreve as pessoas do estágio do slide selecionado, sem tocar o outro estágio', function (): void {
    $retrospective = retrospectiveWithOrder();
    $user = User::factory()->create(['username' => 'fulana']);

    livewire(BuildDeck::class, ['record' => $retrospective->getKey()])
        ->call('select', InspectorMode::Promotion->value.':'.PromotionSection::TAG)
        ->fillForm([
            'visible' => true,
            'people' => [['user_id' => $user->id, 'reason' => 'revisou PR de todo mundo']],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $config = $retrospective->fresh()->deck_config;

    expect($config->promotionsFor(PromotionStage::Promoted))->toHaveCount(1)
        ->and($config->promotionsFor(PromotionStage::Promoted)[0]->userId)->toBe($user->id)
        ->and($config->promotionsFor(PromotionStage::Promoted)[0]->reason)->toBe('revisou PR de todo mundo')
        ->and($config->promotionsFor(PromotionStage::Spotlight))->toBeEmpty();
});

it('põe o ritual entre os slides das fontes e o fecho', function (): void {
    $retrospective = publishedRetrospectiveWithPromotions();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->getKey()]);
    $instance = $page->instance();

    $offset = $instance->composedOffset() + count($instance->composedKinds);

    expect($instance->promotionOffset())->toBe($offset)
        ->and($instance->promotionKinds)->toBe([PromotionSection::SPOTLIGHT, PromotionSection::TAG])
        ->and($instance->closingIndex())->toBe($offset + 2)
        ->and($instance->slideTotal())->toBe($offset + 3);

    $page->call('select', InspectorMode::Promotion->value.':'.PromotionSection::TAG);

    expect($page->instance()->previewIndex())->toBe($offset + 1);
});

it('navegar até o slide do ritual dentro do deck seleciona o ritual, não o fecho', function (): void {
    $retrospective = publishedRetrospectiveWithPromotions();

    $page = livewire(BuildDeck::class, ['record' => $retrospective->getKey()]);
    $offset = $page->instance()->promotionOffset();

    $page->call('selectByIndex', $offset)
        ->assertSet('selection', 'promotion:'.PromotionSection::SPOTLIGHT)
        ->call('selectByIndex', $offset + 1)
        ->assertSet('selection', 'promotion:'.PromotionSection::TAG)
        ->call('selectByIndex', $offset + 2)
        ->assertSet('selection', InspectorMode::Closing->value);
});

it('a tira numera as miniaturas com o MESMO deslocamento do deck', function (): void {
    $retrospective = publishedRetrospectiveWithGithub();

    $instance = livewire(BuildDeck::class, ['record' => $retrospective->getKey()])->instance();

    $indices = [];

    foreach ($instance->filmstrip() as $group) {
        foreach ($group->slides as $slide) {
            if ($slide->index !== null) {
                $indices[] = $slide->index;
            }
        }
    }

    // A primeira miniatura navegável cai logo depois da capa e da seção fixa —
    // não em 1. Quando isto quebrou, clicar num slide acendia o vizinho três
    // posições à frente.
    expect($indices)->not->toBeEmpty()
        ->and(min($indices))->toBe($instance->composedOffset())
        ->and($indices)->toBe(array_unique($indices));
});

test('person search only offers who has a linked account', function (): void {
    $linked = User::factory()->create(['name' => 'Ada Lovelace', 'username' => 'ada']);
    $loose = User::factory()->create(['name' => 'Ada Sem Conta', 'username' => 'ada-solta']);

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $linked->id,
        'provider' => IdentityProvider::Discord,
        'disconnected_at' => null,
    ]);

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $loose->id,
        'provider' => IdentityProvider::Discord,
        'disconnected_at' => now(),
    ]);

    $results = PromotablePeople::search('ada');

    expect($results)->toHaveKey($linked->id)
        ->and($results[$linked->id])->toBe('Ada Lovelace (@ada)')
        ->and($results)->not->toHaveKey($loose->id);
});

test('person label ignores an id that is not a uuid', function (): void {
    expect(PromotablePeople::labelFor('u2'))->toBeNull();
});
