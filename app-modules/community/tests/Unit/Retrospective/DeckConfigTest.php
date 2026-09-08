<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\Enums\PromotionStage;

it('faz round-trip do payload sem perder curadoria', function (): void {
    $config = new DeckConfig(
        order: ['github', 'discord'],
        hiddenSources: ['discord'],
        hiddenSlides: ['github.repos'],
        exclusions: ['github' => ['pr:1', 'pr:2']],
    );

    $restored = DeckConfig::makeFromPayload($config->toArray());

    expect($restored)->toEqual($config);
});

it('usa defaults sãos com payload vazio', function (): void {
    $config = DeckConfig::makeFromPayload([]);

    expect($config->order)->toBeEmpty()
        ->and($config->hiddenSources)->toBeEmpty()
        ->and($config->exclusions)->toBeEmpty()
        ->and($config->showsSource('github'))->toBeTrue()
        ->and($config->showsSlide('github.repos'))->toBeTrue();
});

it('descarta tipos inválidos ao reidratar', function (): void {
    $config = DeckConfig::makeFromPayload([
        'order' => ['github', 42, null],
        'hidden_sources' => 'nope',
        'exclusions' => ['github' => ['pr:1', 99], 7 => ['x']],
    ]);

    expect($config->order)->toBe(['github'])
        ->and($config->hiddenSources)->toBeEmpty()
        ->and($config->exclusionsFor('github'))->toBe(['pr:1']);
});

it('responde aos helpers de visibilidade, posição e exclusions', function (): void {
    $config = new DeckConfig(
        order: ['github', 'discord'],
        hiddenSources: ['discord'],
        hiddenSlides: ['github.repos'],
        exclusions: ['github' => ['pr:1'], 'discord' => ['actor:x']],
    );

    expect($config->showsSource('discord'))->toBeFalse()
        ->and($config->showsSource('github'))->toBeTrue()
        ->and($config->showsSlide('github.repos'))->toBeFalse()
        ->and($config->position('discord'))->toBe(1)
        ->and($config->position('twitch'))->toBe(2)
        ->and($config->exclusionsFor('github'))->toBe(['pr:1'])
        ->and($config->allExclusions())->toEqualCanonicalizing(['pr:1', 'actor:x']);
});

it('esconde e revela uma fonte sem mutar a cópia original', function (): void {
    $config = new DeckConfig(order: ['github', 'discord']);

    $hidden = $config->withSourceVisible('discord', visible: false);

    expect($hidden->hiddenSources)->toBe(['discord'])
        ->and($config->hiddenSources)->toBeEmpty()
        ->and($hidden->withSourceVisible('discord', visible: true)->hiddenSources)->toBeEmpty();
});

it('não duplica a fonte já escondida', function (): void {
    $config = new DeckConfig(hiddenSources: ['discord']);

    expect($config->withSourceVisible('discord', visible: false)->hiddenSources)->toBe(['discord']);
});

it('esconde e revela um kind de slide sem mutar a cópia original', function (): void {
    $config = new DeckConfig();

    $hidden = $config->withSlideVisible('github.repos', visible: false);

    expect($hidden->hiddenSlides)->toBe(['github.repos'])
        ->and($config->hiddenSlides)->toBeEmpty()
        ->and($hidden->withSlideVisible('github.repos', visible: true)->hiddenSlides)->toBeEmpty();
});

it('troca a ordem editorial preservando o resto da curadoria', function (): void {
    $config = new DeckConfig(
        order: ['github', 'discord'],
        hiddenSources: ['discord'],
        hiddenSlides: ['github.repos'],
        exclusions: ['github' => ['pr:1']],
    );

    $reordered = $config->withOrder(['discord', 'github']);

    expect($reordered->order)->toBe(['discord', 'github'])
        ->and($reordered->hiddenSources)->toBe(['discord'])
        ->and($reordered->hiddenSlides)->toBe(['github.repos'])
        ->and($reordered->exclusionsFor('github'))->toBe(['pr:1'])
        ->and($config->order)->toBe(['github', 'discord']);
});

it('substitui os refs excluídos de uma fonte sem tocar nas outras', function (): void {
    $config = new DeckConfig(exclusions: ['github' => ['pr:1'], 'discord' => ['member:x']]);

    $updated = $config->withExclusionsFor('github', ['pr:9', 'actor:maria']);

    expect($updated->exclusionsFor('github'))->toBe(['pr:9', 'actor:maria'])
        ->and($updated->exclusionsFor('discord'))->toBe(['member:x'])
        ->and($config->exclusionsFor('github'))->toBe(['pr:1']);
});

it('remove a chave da fonte quando a lista de exclusions esvazia', function (): void {
    $config = new DeckConfig(exclusions: ['github' => ['pr:1'], 'discord' => ['member:x']]);

    $updated = $config->withExclusionsFor('github', []);

    expect($updated->exclusions)->toBe(['discord' => ['member:x']]);
});

it('normaliza os refs recebidos, sem vazios nem repetidos', function (): void {
    $config = new DeckConfig();

    $updated = $config->withExclusionsFor('github', ['pr:1', ' pr:2 ', '', 'pr:1']);

    expect($updated->exclusionsFor('github'))->toBe(['pr:1', 'pr:2']);
});

it('faz round-trip das promoções e descarta escolha corrompida', function (): void {
    $config = new DeckConfig(promotions: [
        new PromotionEntry('u1', PromotionStage::Spotlight, 'segurou o #ajuda'),
        new PromotionEntry('u2', PromotionStage::Promoted),
    ]);

    expect(DeckConfig::makeFromPayload($config->toArray()))->toEqual($config);

    $sujo = DeckConfig::makeFromPayload([
        'promotions' => [
            ['user_id' => 'u1', 'stage' => 'promoted'],
            ['user_id' => '', 'stage' => 'promoted'],
            ['user_id' => 'u3', 'stage' => 'estagio-que-nao-existe'],
            'nem array',
        ],
    ]);

    expect($sujo->promotions)->toHaveCount(1)
        ->and($sujo->promotions[0]->userId)->toBe('u1');
});

it('substitui um estágio sem tocar o outro', function (): void {
    $config = new DeckConfig(promotions: [
        new PromotionEntry('u1', PromotionStage::Spotlight),
        new PromotionEntry('u2', PromotionStage::Promoted),
    ]);

    $novo = $config->withPromotionsFor(PromotionStage::Promoted, [new PromotionEntry('u9', PromotionStage::Promoted)]);

    expect($novo->promotionsFor(PromotionStage::Promoted))->toHaveCount(1)
        ->and($novo->promotionsFor(PromotionStage::Promoted)[0]->userId)->toBe('u9')
        ->and($novo->promotionsFor(PromotionStage::Spotlight))->toHaveCount(1)
        ->and($novo->promotionsFor(PromotionStage::Spotlight)[0]->userId)->toBe('u1')
        // A cópia original segue intacta.
        ->and($config->promotionsFor(PromotionStage::Promoted)[0]->userId)->toBe('u2');
});

it('preserva as promoções ao mexer em ordem, on/off e exclusions', function (): void {
    $config = new DeckConfig(promotions: [new PromotionEntry('u1', PromotionStage::Promoted)]);

    $mexido = $config
        ->withOrder(['discord'])
        ->withSourceVisible('discord', visible: false)
        ->withSlideVisible('github.repos', visible: false)
        ->withExclusionsFor('github', ['pr:1']);

    expect($mexido->promotions)->toHaveCount(1)
        ->and($mexido->promotions[0]->userId)->toBe('u1');
});

it('guarda os apresentadores em ordem, sem repetição, e os preserva nas outras escritas', function (): void {
    $config = new DeckConfig(order: ['github'])->withHosts(['u2', 'u1', 'u2', '']);

    expect($config->hosts)->toBe(['u2', 'u1'])
        ->and($config->withSourceVisible('discord', visible: false)->hosts)->toBe(['u2', 'u1'])
        ->and(DeckConfig::makeFromPayload($config->toArray())->hosts)->toBe(['u2', 'u1'])
        ->and($config->withHosts([])->hosts)->toBeEmpty();
});
