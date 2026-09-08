<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Actions\ComposePromotions;
use He4rt\Community\Retrospective\Actions\PublishRetrospective;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Jobs\CompileRetrospectiveSnapshot;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Community\Retrospective\Slides\FrozenSlide;
use Illuminate\Support\Facades\Bus;

it('marca "publicando" e despacha o job de congelamento', function (): void {
    Bus::fake();

    $retrospective = Retrospective::factory()->create();

    resolve(PublishRetrospective::class)->execute($retrospective);

    expect($retrospective->fresh()->status)->toBe(RetrospectiveStatus::Publishing);

    Bus::assertDispatched(CompileRetrospectiveSnapshot::class);
});

it('o job congela o snapshot e promove para publicado', function (): void {
    $retrospective = Retrospective::factory()->create([
        'since' => CarbonImmutable::parse('2026-06-01'),
        'until' => CarbonImmutable::parse('2026-06-30'),
        'status' => RetrospectiveStatus::Publishing,
    ]);

    $source = new class implements RetrospectiveSource
    {
        public function key(): string
        {
            return 'github';
        }

        public function label(): string
        {
            return 'GitHub';
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult('github', 'GitHub', new HeadlineMetrics([new Metric('PRs', 3)]), [
                new FrozenSlide('github.panorama', ['meta' => ['prs' => 3]]),
            ]);
        }
    };

    new CompileRetrospectiveSnapshot($retrospective)->handle(new CompileSnapshot([$source], resolve(ComposePromotions::class)));

    $fresh = $retrospective->fresh();

    expect($fresh->status)->toBe(RetrospectiveStatus::Published)
        ->and($fresh->published_at)->not->toBeNull()
        ->and($fresh->snapshot->sources)->toHaveCount(1)
        ->and($fresh->snapshot->sources[0]->key)->toBe('github');
});
