<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Events\InteractionTracked;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

final readonly class TrackActivity
{
    public function handle(TrackActivityDTO $dto): Interaction
    {
        $identity = ExternalIdentity::query()->findOrFail($dto->externalIdentityId);

        $interaction = Interaction::query()->firstOrCreate(
            ['external_ref' => $dto->externalRef],
            [
                'external_identity_id' => $identity->id,
                // Derivado da identidade, nunca recebido: o DTO não pode divergir do dono real.
                'user_id' => $identity->model_id,
                'type' => $dto->type,
                'attributed_by' => $dto->attributedBy,
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId,
                'occurred_at' => $dto->occurredAt,
            ],
        );

        if ($interaction->wasRecentlyCreated) {
            event(new InteractionTracked($interaction));
        }

        return $interaction;
    }
}
