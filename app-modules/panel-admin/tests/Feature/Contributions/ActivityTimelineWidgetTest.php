<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\PanelAdmin\Contributions\Timeline\DailyActivitySeries;
use He4rt\PanelAdmin\Contributions\Widgets\ActivityTimelineWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->timezone = config('app.display_timezone');
    $this->series = resolve(DailyActivitySeries::class);

    $this->window = function (int $days = 5): array {
        $until = CarbonImmutable::now($this->timezone)->endOfDay();

        return [$until->subDays($days - 1)->startOfDay(), $until];
    };

    $this->dayOf = function (array $days, string $date): object {
        $match = array_values(array_filter(
            $days,
            static fn ($day): bool => $day->date->toDateString() === $date,
        ));

        expect($match)->not->toBeEmpty("nenhum dia para {$date}");

        return $match[0];
    };
});

test('the widget renders with the timeline heading', function (): void {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    livewire(ActivityTimelineWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-admin::contributions.timeline.heading', [
            'days' => 32,
            'timezone' => __('panel-admin::contributions.timeline.timezones.America/Sao_Paulo'),
        ]));
});

test('the admin dashboard carries the timeline widget', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(Filament::getWidgets())->toContain(ActivityTimelineWidget::class);
});

test('every day of the window comes back, even the ones with no rows', function (): void {
    [$since, $until] = ($this->window)(5);

    $series = $this->series->between($since, $until);

    expect($series->days)->toHaveCount(5)
        ->and($series->days[0]->date->toDateString())->toBe($since->toDateString())
        ->and($series->days[4]->date->toDateString())->toBe($until->toDateString())
        ->and($series->days[2]->github->total)->toBe(0)
        ->and($series->days[2]->messages->messages)->toBe(0)
        ->and($series->days[2]->voice->sessions)->toBe(0);
});

test('github contributions land on the right day, split by type', function (): void {
    [$since, $until] = ($this->window)(5);
    $day = $since->addDay();

    GithubContribution::factory()->count(3)->create([
        'type' => ContributionType::Pr,
        'occurred_at' => $day->setTime(10, 0),
        'actor_login' => 'ana',
    ]);
    GithubContribution::factory()->create([
        'type' => ContributionType::Review,
        'occurred_at' => $day->setTime(11, 0),
        'actor_login' => 'bruno',
    ]);

    $series = $this->series->between($since, $until);
    $row = ($this->dayOf)($series->days, $day->toDateString())->github;

    expect($row->total)->toBe(4)
        ->and($row->prs)->toBe(3)
        ->and($row->reviews)->toBe(1)
        ->and($row->commits)->toBe(0)
        ->and($row->people)->toBe(2);
});

test('bot contributions never reach the timeline', function (): void {
    [$since, $until] = ($this->window)(5);
    $day = $since->addDay();

    GithubContribution::factory()->create(['occurred_at' => $day->setTime(9, 0), 'actor_login' => 'ana']);
    GithubContribution::factory()->create(['occurred_at' => $day->setTime(9, 0), 'actor_login' => 'dependabot[bot]']);
    GithubContribution::factory()->create([
        'occurred_at' => $day->setTime(9, 0),
        'actor_login' => 'renovate',
        'metadata' => ['is_bot' => true],
    ]);

    $series = $this->series->between($since, $until);

    expect(($this->dayOf)($series->days, $day->toDateString())->github->total)->toBe(1);
});

test('bot messages are dropped but pre-column messages still count', function (): void {
    [$since, $until] = ($this->window)(5);
    $day = $since->addDay();

    Message::factory()->create(['sent_at' => $day->setTime(8, 0), 'source_kind' => MessageSourceKind::User, 'obtained_experience' => 10]);
    Message::factory()->create(['sent_at' => $day->setTime(8, 0), 'source_kind' => null, 'obtained_experience' => 5]);
    Message::factory()->create(['sent_at' => $day->setTime(8, 0), 'source_kind' => MessageSourceKind::Bot, 'obtained_experience' => 99]);

    $row = ($this->dayOf)($this->series->between($since, $until)->days, $day->toDateString())->messages;

    expect($row->messages)->toBe(2)
        ->and($row->people)->toBe(2)
        ->and($row->xp)->toBe(15);
});

test('a voice session is the join, not the pair of presence rows', function (): void {
    [$since, $until] = ($this->window)(5);
    $day = $since->addDay();

    $voice = Voice::factory()->count(2)->joined()->create(['occurred_at' => $day->setTime(20, 0), 'obtained_experience' => 7]);
    Voice::factory()->left()->create([
        'occurred_at' => $day->setTime(21, 0),
        'external_identity_id' => $voice->first()->external_identity_id,
        'obtained_experience' => 0,
    ]);

    $row = ($this->dayOf)($this->series->between($since, $until)->days, $day->toDateString())->voice;

    expect($row->sessions)->toBe(2)
        ->and($row->people)->toBe(2)
        ->and($row->xp)->toBe(14);
});

test('an event past midnight stays on the Brasilia day, not the UTC one', function (): void {
    [$since, $until] = ($this->window)(5);
    $day = $since->addDay();

    // 22h em Brasília já é o dia seguinte em UTC: agrupar em UTC moveria a linha.
    Message::factory()->create(['sent_at' => $day->setTime(22, 30)]);

    $days = $this->series->between($since, $until)->days;

    expect(($this->dayOf)($days, $day->toDateString())->messages->messages)->toBe(1)
        ->and(($this->dayOf)($days, $day->addDay()->toDateString())->messages->messages)->toBe(0);
});

test('dataUntil follows the source that stopped first', function (): void {
    [$since, $until] = ($this->window)(5);

    Message::factory()->create(['sent_at' => $until->startOfDay()->setTime(12, 0)]);
    Voice::factory()->joined()->create(['occurred_at' => $until->startOfDay()->setTime(12, 0)]);
    GithubContribution::factory()->create(['occurred_at' => $since->addDays(2)->setTime(12, 0), 'actor_login' => 'ana']);

    $meta = $this->series->between($since, $until)->meta;

    expect($meta->dataUntil->toDateString())->toBe($since->addDays(2)->toDateString());
});

test('a source with no rows at all does not drag the whole window back', function (): void {
    [$since, $until] = ($this->window)(5);

    Message::factory()->create(['sent_at' => $until->startOfDay()->setTime(12, 0)]);

    $meta = $this->series->between($since, $until)->meta;

    expect($meta->dataUntil->toDateString())->toBe($until->toDateString());
});

test('type totals match the daily columns they name', function (): void {
    [$since, $until] = ($this->window)(5);

    GithubContribution::factory()->count(2)->create([
        'type' => ContributionType::Commit,
        'occurred_at' => $since->addDay()->setTime(12, 0),
        'actor_login' => 'ana',
    ]);

    $series = $this->series->between($since, $until);
    $commits = array_values(array_filter($series->types, static fn ($type): bool => $type->key === 'commits'));

    expect($commits[0]->count)->toBe(2)
        ->and($commits[0]->label)->toBe('commits');
});

test('the payload handed to alpine keeps the day keys the component reads', function (): void {
    [$since, $until] = ($this->window)(3);

    $payload = $this->series->between($since, $until)->toArray();

    expect($payload['days'][0])->toHaveKeys(['date', 'gh', 'ms', 'vc'])
        ->and($payload['days'][0]['gh'])->toHaveKeys(['total', 'prs', 'reviews', 'commits', 'issues', 'comments', 'reviewComments', 'people'])
        ->and($payload['days'][0]['ms'])->toHaveKeys(['messages', 'people', 'xp'])
        ->and($payload['days'][0]['vc'])->toHaveKeys(['sessions', 'people', 'xp'])
        ->and($payload['meta'])->toHaveKeys(['since', 'until', 'dataUntil', 'days', 'timezone']);
});
