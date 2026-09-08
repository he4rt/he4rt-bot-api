<?php

declare(strict_types=1);

use Database\Seeders\RetrospectiveDemoSeeder;
use He4rt\Activity\Retrospective\DiscordSource;
use He4rt\Community\Database\Seeders\RetrospectiveSeeder;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\DTOs\ExclusionCandidate;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\IntegrationGithub\Retrospective\GithubSource;

/*
 * O seeder de demonstração só serve se o dado cair DENTRO dos recortes das
 * edições — e isso depende de três seeders concordarem sobre a linha do tempo.
 * Uma janela deslocada em um mês faz o playground abrir vazio sem erro nenhum,
 * então é exatamente isso que estes testes prendem.
 */

/**
 * Passa pelo DatabaseSeeder inteiro, não só pelo de demonstração: é o caminho que
 * a documentação manda rodar (`migrate:fresh --seed`) e o único que exercita a
 * convivência com o BaseSeeder — que cria o admin `danielhe4rt` antes, com o mesmo
 * username que a lista de membros do Discord reivindica (e `users.username` é único).
 */
it('monta uma edição para cada estado do ciclo editorial', function (): void {
    $this->seed();

    $statuses = Retrospective::query()->pluck('status', 'title');

    expect($statuses)->toHaveCount(6)
        ->and($statuses[RetrospectiveSeeder::RICH_DRAFT])->toBe(RetrospectiveStatus::Draft)
        ->and($statuses[RetrospectiveSeeder::CURATED_DRAFT])->toBe(RetrospectiveStatus::Draft)
        ->and($statuses[RetrospectiveSeeder::PUBLISHED])->toBe(RetrospectiveStatus::Published)
        ->and($statuses[RetrospectiveSeeder::DRIFTED])->toBe(RetrospectiveStatus::Published)
        ->and($statuses[RetrospectiveSeeder::PUBLISHING])->toBe(RetrospectiveStatus::Publishing)
        ->and($statuses[RetrospectiveSeeder::ARCHIVED])->toBe(RetrospectiveStatus::Draft);

    // Publicada de verdade = snapshot congelado com as duas fontes dentro.
    $published = Retrospective::query()->where('title', RetrospectiveSeeder::PUBLISHED)->sole();

    expect($published->snapshot)->not->toBeNull()
        ->and($published->snapshot->sources)->toHaveCount(2)
        ->and($published->needsRepublish())->toBeFalse();
});

it('deixa o rascunho principal com dado das duas fontes e iscas no picker', function (): void {
    $this->seed(RetrospectiveDemoSeeder::class);

    $draft = Retrospective::query()->where('title', RetrospectiveSeeder::RICH_DRAFT)->sole();
    $snapshot = resolve(CompileSnapshot::class)->execute($draft->period(), $draft->filters());

    expect($snapshot->sources)->toHaveCount(2);

    foreach ($snapshot->sources as $source) {
        expect($source->slides)->not->toBeEmpty();
    }

    expect(resolve(GithubSource::class)->exclusionCandidates($draft->period()))->not->toBeEmpty()
        ->and(resolve(DiscordSource::class)->exclusionCandidates($draft->period()))->not->toBeEmpty();
});

/**
 * A isca só serve se a MESMA fonte que a esconde a ofereça de volta no picker —
 * senão o operador não consegue desmarcar o que o seeder pré-excluiu (e o
 * ExclusionPicker trataria tudo como órfão). Prende a concordância entre a janela
 * `spotlight` dos seeders de fonte e o recorte da edição já curada.
 */
it('oferece no picker as mesmas iscas que a edição curada já esconde', function (): void {
    $this->seed(RetrospectiveDemoSeeder::class);

    $curated = Retrospective::query()->where('title', RetrospectiveSeeder::CURATED_DRAFT)->sole();

    foreach ([GithubSource::class, DiscordSource::class] as $class) {
        $source = resolve($class);
        $excluded = $curated->deck_config->exclusionsFor($source->key());
        $offered = array_map(
            static fn (ExclusionCandidate $candidate): string => $candidate->ref,
            $source->exclusionCandidates($curated->period()),
        );

        expect($excluded)->not->toBeEmpty()
            ->and($offered)->toContain(...$excluded);
    }
});

it('deixa a edição de drift pedindo republicação e o arquivo vazio', function (): void {
    $this->seed(RetrospectiveDemoSeeder::class);

    // Publicada antes da exclusion: a curadoria de dado passou a discordar do
    // snapshot congelado.
    expect(Retrospective::query()->where('title', RetrospectiveSeeder::DRIFTED)->sole()->needsRepublish())->toBeTrue();

    $archived = Retrospective::query()->where('title', RetrospectiveSeeder::ARCHIVED)->sole();

    expect(resolve(CompileSnapshot::class)->execute($archived->period(), $archived->filters())->sources)
        ->toBeEmpty();
});
