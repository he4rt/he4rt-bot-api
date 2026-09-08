<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaction>
 */
final class InteractionFactory extends Factory
{
    protected $model = Interaction::class;

    /**
     * `fake()->unique()` devolve um gerador novo a cada chamada, então não garante
     * nada entre duas interações. O external_ref tem índice único: o contador é o
     * que impede a colisão.
     */
    private static int $sequence = 0;

    public function definition(): array
    {
        $identity = ExternalIdentity::factory()
            ->for(User::factory(), 'model')
            ->state(['provider' => IdentityProvider::DevTo]);

        return [
            'external_identity_id' => $identity,
            'user_id' => fn (array $attributes): string => ExternalIdentity::query()
                ->findOrFail($attributes['external_identity_id'])
                ->model_id,
            'type' => ActivityType::Article,
            'attributed_by' => AttributionMethod::Owned,
            'external_ref' => fn (): string => 'devto:article:'.$this->nextSequence(),
            'occurred_at' => now(),
        ];
    }

    public function forIdentity(ExternalIdentity $identity): self
    {
        return $this->state([
            'external_identity_id' => $identity->id,
            'user_id' => $identity->model_id,
        ]);
    }

    public function ofType(ActivityType $type): self
    {
        return $this->state([
            'type' => $type,
            // Closure, não valor: um state literal é avaliado uma vez e repetiria
            // o mesmo ref em toda a leva de um ->count().
            'external_ref' => fn (): string => 'github:'.$type->value.':he4rt/heartdevs.com:'.$this->nextSequence(),
        ]);
    }

    public function hidden(): self
    {
        return $this->state([
            'hidden_at' => now(),
        ]);
    }

    private function nextSequence(): int
    {
        return ++self::$sequence;
    }
}
