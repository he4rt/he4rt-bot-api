<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\ComposePromotions;
use He4rt\Community\Retrospective\Contracts\MeasuresPerson;
use He4rt\Community\Retrospective\Contracts\PersonDirectory;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PersonAccount;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\PromotionStage;

function promoPeriod(): Period
{
    return new Period(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-12-31'));
}

/**
 * Fonte que mede pessoa, com resposta fixa por provider.
 *
 * @param  list<Metric>  $metrics
 */
function promoMeasuringSource(string $key, string $label, string $provider, array $metrics): RetrospectiveSource
{
    return new readonly class($key, $label, $provider, $metrics) implements MeasuresPerson, RetrospectiveSource
    {
        /**
         * @param  list<Metric>  $metrics
         */
        public function __construct(
            private string $key,
            private string $label,
            private string $provider,
            private array $metrics,
        ) {}

        public function key(): string
        {
            return $this->key;
        }

        public function label(): string
        {
            return $this->label;
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult($this->key, $this->label, new HeadlineMetrics(), []);
        }

        public function measure(PersonIdentity $person, Period $period, SourceFilters $filters): array
        {
            return $person->account($this->provider) instanceof PersonAccount ? $this->metrics : [];
        }
    };
}

/** Fonte crua: entra no deck, mas não sabe medir ninguém. */
function promoBlindSource(): RetrospectiveSource
{
    return new class implements RetrospectiveSource
    {
        public function key(): string
        {
            return 'cega';
        }

        public function label(): string
        {
            return 'Cega';
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult('cega', 'Cega', new HeadlineMetrics(), []);
        }
    };
}

/**
 * @param  array<string, PersonIdentity>  $people
 */
function promoResolver(array $people): PersonDirectory
{
    return new readonly class($people) implements PersonDirectory
    {
        /**
         * @param  array<string, PersonIdentity>  $people
         */
        public function __construct(private array $people) {}

        public function execute(array $userIds): array
        {
            return array_intersect_key($this->people, array_flip($userIds));
        }
    };
}

function promoPerson(string $id, string $provider = 'discord'): PersonIdentity
{
    return new PersonIdentity(
        userId: $id,
        name: 'Fulana '.$id,
        username: 'fulana'.$id,
        avatar: 'https://example.test/'.$id.'.png',
        accounts: [$provider => new PersonAccount(identityId: 'ident-'.$id, accountId: '42', username: 'fulana')],
    );
}

it('empilha as métricas por fonte, sem somar plataformas diferentes', function (): void {
    $compose = new ComposePromotions(
        [
            promoMeasuringSource('discord', 'Discord', 'discord', [new Metric('Mensagens', 8_132)]),
            promoMeasuringSource('github', 'GitHub', 'github', [new Metric('PRs', 47)]),
        ],
        promoResolver(['u1' => new PersonIdentity(
            userId: 'u1',
            name: 'Fulana',
            username: 'fulana',
            avatar: 'a.png',
            accounts: [
                'discord' => new PersonAccount('ident-1'),
                'github' => new PersonAccount('ident-2', accountId: '31713982'),
            ],
        )]),
    );

    $cards = $compose->execute(
        [new PromotionEntry('u1', PromotionStage::Promoted, 'segurou o #ajuda')],
        promoPeriod(),
        new SourceFilters(),
    );

    expect($cards)->toHaveCount(1)
        ->and($cards[0]->name)->toBe('Fulana')
        ->and($cards[0]->reason)->toBe('segurou o #ajuda')
        ->and($cards[0]->stage)->toBe(PromotionStage::Promoted)
        ->and($cards[0]->groups)->toHaveCount(2)
        ->and($cards[0]->groups[0]->sourceLabel)->toBe('Discord')
        ->and($cards[0]->groups[0]->metrics[0]->value)->toBe(8_132)
        ->and($cards[0]->groups[1]->sourceLabel)->toBe('GitHub')
        ->and($cards[0]->groups[1]->metrics[0]->value)->toBe(47);
});

it('não cria faixa para a fonte em que a pessoa não tem conta', function (): void {
    $compose = new ComposePromotions(
        [
            promoMeasuringSource('discord', 'Discord', 'discord', [new Metric('Mensagens', 10)]),
            promoMeasuringSource('github', 'GitHub', 'github', [new Metric('PRs', 5)]),
        ],
        promoResolver(['u1' => promoPerson('u1', provider: 'discord')]),
    );

    $cards = $compose->execute([new PromotionEntry('u1', PromotionStage::Spotlight)], promoPeriod(), new SourceFilters());

    expect($cards[0]->groups)->toHaveCount(1)
        ->and($cards[0]->groups[0]->sourceKey)->toBe('discord');
});

it('ignora a fonte que não sabe medir pessoa', function (): void {
    $compose = new ComposePromotions(
        [promoBlindSource(), promoMeasuringSource('discord', 'Discord', 'discord', [new Metric('Mensagens', 1)])],
        promoResolver(['u1' => promoPerson('u1')]),
    );

    $cards = $compose->execute([new PromotionEntry('u1', PromotionStage::Spotlight)], promoPeriod(), new SourceFilters());

    expect($cards[0]->groups)->toHaveCount(1)
        ->and($cards[0]->groups[0]->sourceKey)->toBe('discord');
});

it('descarta quem não existe mais ou não tem conta nenhuma', function (): void {
    $semConta = new PersonIdentity('u2', 'Sicrana', 'sicrana', 'b.png', accounts: []);

    $compose = new ComposePromotions(
        [promoMeasuringSource('discord', 'Discord', 'discord', [new Metric('Mensagens', 1)])],
        promoResolver(['u1' => promoPerson('u1'), 'u2' => $semConta]),
    );

    $cards = $compose->execute(
        [
            new PromotionEntry('u1', PromotionStage::Spotlight),
            new PromotionEntry('u2', PromotionStage::Spotlight),
            new PromotionEntry('apagado', PromotionStage::Spotlight),
        ],
        promoPeriod(),
        new SourceFilters(),
    );

    expect($cards)->toHaveCount(1)
        ->and($cards[0]->userId)->toBe('u1');
});

it('não toca fonte nenhuma quando ninguém foi escolhido', function (): void {
    $compose = new ComposePromotions(
        [promoMeasuringSource('discord', 'Discord', 'discord', [new Metric('Mensagens', 1)])],
        promoResolver([]),
    );

    expect($compose->execute([], promoPeriod(), new SourceFilters()))->toBeEmpty();
});
