<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
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
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\PromotionStage;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

function retroFakeSource(string $key, string $label, bool $empty): RetrospectiveSource
{
    return new readonly class($key, $label, $empty) implements RetrospectiveSource
    {
        public function __construct(
            private string $sourceKey,
            private string $sourceLabel,
            private bool $empty,
        ) {}

        public function key(): string
        {
            return $this->sourceKey;
        }

        public function label(): string
        {
            return $this->sourceLabel;
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult(
                $this->sourceKey,
                $this->sourceLabel,
                new HeadlineMetrics($this->empty ? [] : [new Metric('Itens', 1)]),
                $this->empty ? [] : [new FrozenSlide($this->sourceKey.'.panel', ['n' => 1])],
            );
        }
    };
}

function retroPeriod(): Period
{
    return Period::of(CarbonImmutable::parse('2026-06-01'), CarbonImmutable::parse('2026-06-30'));
}

it('coleta as fontes e empacota os SourceResult crus', function (): void {
    $snapshot = new CompileSnapshot([
        retroFakeSource('github', 'GitHub', empty: false),
        retroFakeSource('discord', 'Discord', empty: false),
    ], resolve(ComposePromotions::class))->execute(retroPeriod(), new SourceFilters());

    expect($snapshot->sources)->toHaveCount(2)
        ->and(array_map(fn (SourceResult $source): string => $source->key, $snapshot->sources))
        ->toBe(['github', 'discord']);
});

it('descarta fontes sem dado no recorte', function (): void {
    $snapshot = new CompileSnapshot([
        retroFakeSource('github', 'GitHub', empty: false),
        retroFakeSource('discord', 'Discord', empty: true),
    ], resolve(ComposePromotions::class))->execute(retroPeriod(), new SourceFilters());

    expect($snapshot->sources)->toHaveCount(1)
        ->and($snapshot->sources[0]->key)->toBe('github');
});

it('devolve snapshot vazio quando não há fontes', function (): void {
    $snapshot = new CompileSnapshot([], resolve(ComposePromotions::class))->execute(retroPeriod(), new SourceFilters());

    expect($snapshot->isEmpty())->toBeTrue();
});

it('congela as promoções junto dos números das fontes', function (): void {
    $pessoa = new PersonIdentity('u1', 'Fulana', 'fulana', 'a.png', accounts: [
        'discord' => new PersonAccount('ident-1'),
    ]);

    $fonte = new class implements MeasuresPerson, RetrospectiveSource
    {
        public function key(): string
        {
            return 'discord';
        }

        public function label(): string
        {
            return 'Discord';
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult('discord', 'Discord', new HeadlineMetrics([new Metric('Mensagens', 10)]), []);
        }

        /**
         * @return Metric[]
         */
        public function measure(PersonIdentity $person, Period $period, SourceFilters $filters): array
        {
            return [new Metric('Mensagens', 8_132)];
        }
    };

    $directory = new readonly class($pessoa) implements PersonDirectory
    {
        public function __construct(private PersonIdentity $pessoa) {}

        /**
         * @return PersonIdentity[]
         */
        public function execute(array $userIds): array
        {
            return ['u1' => $this->pessoa];
        }
    };

    $snapshot = new CompileSnapshot([$fonte], new ComposePromotions([$fonte], $directory))
        ->execute(
            retroPeriod(),
            new SourceFilters(),
            [new PromotionEntry('u1', PromotionStage::Promoted, 'segurou o #ajuda')],
        );

    expect($snapshot->promotions)->toHaveCount(1)
        ->and($snapshot->promotions[0]->name)->toBe('Fulana')
        ->and($snapshot->promotions[0]->groups[0]->metrics[0]->value)->toBe(8_132);

    // E sobrevive ao round-trip do jsonb: é assim que a página pública lê.
    $reidratado = RetrospectiveSnapshot::makeFromPayload($snapshot->toArray());

    expect($reidratado->promotions)->toEqual($snapshot->promotions)
        ->and($reidratado->promotionSignatures())->toBe(['u1|promoted|segurou o #ajuda']);
});
