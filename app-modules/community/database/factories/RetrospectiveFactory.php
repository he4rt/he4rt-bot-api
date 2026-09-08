<?php

declare(strict_types=1);

namespace He4rt\Community\Database\Factories;

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Retrospective>
 */
final class RetrospectiveFactory extends Factory
{
    protected $model = Retrospective::class;

    public function definition(): array
    {
        $since = CarbonImmutable::now()->subMonth()->startOfDay();

        return [
            'id' => fake()->uuid(),
            'title' => 'Retrospectiva de '.fake()->monthName(),
            'since' => $since,
            'until' => CarbonImmutable::now(),
            'status' => RetrospectiveStatus::Draft,
            'cover_kind' => CoverKind::Retrospective,
            'cover_title' => fake()->sentence(3),
            'cover_intro' => fake()->sentence(),
            'closing_text' => fake()->sentence(),
            'hide_bots' => true,
            'deck_config' => new DeckConfig(),
            'snapshot' => null,
            'published_at' => null,
        ];
    }

    public function published(?RetrospectiveSnapshot $snapshot = null): self
    {
        return $this->state(fn (): array => [
            'status' => RetrospectiveStatus::Published,
            'published_at' => CarbonImmutable::now(),
            'snapshot' => $snapshot ?? new RetrospectiveSnapshot(),
        ]);
    }

    /**
     * @param  list<User>  $hosts
     */
    public function onboarding(array $hosts = []): self
    {
        return $this->state(fn (): array => [
            'cover_kind' => CoverKind::Onboarding,
            'deck_config' => new DeckConfig()->withHosts(
                array_map(static fn (User $host): string => $host->id, $hosts),
            ),
        ]);
    }
}
